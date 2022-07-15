<?php
/**
* generate table of contents
*/

/**
 * $content_htmlの中に含まれる$change_htmlのDOMに$idで指定したid属性を追加する
 * $change_htmlは開始タグから閉じタグまでの唯一の1文であると想定している。２文以上ではエラーは吐かずにバグる
 * @return DOMObject $content_html idを追加した$change_htmlを含んだcontent_htmlを返す
 */
function add_id_attribute_to_content ( $content_html, $change_html, $id )
{
  // $change_htmlが既にid属性を持っているか確認
  preg_match( "/id/i", $change_html, $if_included_id );
  $if_included_id = count($if_included_id) !== 0;

  if( $if_included_id ) //id属性を既に持っている
  {
    $modified_html = preg_replace( '/id=([\"\'])?([^\"\']+)/', 'id=$1$2 ' . $id, $change_html );
  }else
  {
    $modified_html = preg_replace( "/^(<h[\w\s]+)>/m", '$1 id="' . $id . '">', $change_html );
  }

  $content_html = str_replace( $change_html, $modified_html, $content_html );

  return $content_html;
}

function create_table_content ( $exploded_content, $index_prefix_name )
{
  $tmp_header_number = 0;
  $previos_header_number = 0;
  $id = 0;

  // 目次のリストの中身部分の作成
  $inner_table = "";
  foreach ( $exploded_content[0] as $one_line_header_html )
  {
    $tmp_header_number = (int) mb_substr( $one_line_header_html, 2, 1 );

    $current_header_number = $tmp_header_number;

    // 子リストの生成
    if( $current_header_number > $previos_header_number ) {

      $inner_table = $inner_table . '<ul class="tr-contents-table__lists tr-contents-table__lists--child">';

    }else{

      // 子リストの閉じタグ生成
      for(
        $r = $current_header_number;
        $r < $previos_header_number;
        $r++
      )
      {
        $inner_table = $inner_table . '</li></ul>';
      }

      if( $inner_table !== '' ){
        $inner_table = $inner_table . '</li>';
      }

    }
    $id_name = $index_prefix_name . $id;

    // 表のlistタグ生成
    $insert_text = preg_replace( '/<\/?[\w\s]+>/', '', $one_line_header_html );

    $inner_table = $inner_table .
    "<li class='tr-contents-table__item'>" .
      "<a class='tr-contents-table__link' href='#{$id_name}'>" .
        esc_html($insert_text) .
      "</a>";

    $previos_header_number = $current_header_number;
    $id++;
  }

  $table_html = '<ol class="tr-contents-table__lists">' . $inner_table . '</ol>';

  $table_wrapper = '<div class="tr-contents-table"><p class="tr-contents-table__title"><span class="tr-contents-table__button"></span>目次</p>' . $table_html . '</div>';

  return $table_wrapper;
}

function troms_table_content ( $content )
{
  // hタグの取得する正規表現。ここでは</h2>と</h3>を取得している
  $tag_patterm = "/^.+?<\/h[23]>$/im";

  // コンテンツ内のhタグに追加する目次用のid属性のプレフィックス
  $index_prefix_name = "troms_table_";

  preg_match_all( $tag_patterm, $content, $exploded_content );

  $table_html = create_table_content( $exploded_content, $index_prefix_name );

  $i=0;
  foreach ($exploded_content[0] as $one_line_header_html )
  {
    $id_name = $index_prefix_name . $i;

    // 本文に表と対応したid属性を追加
    $content = add_id_attribute_to_content( $content, $one_line_header_html, $id_name );
    $i++;
  }

  //コンテンツに目次を挿入
  preg_match( "/^.+?<\/h[23]>$/im", $content, $first_header_html, PREG_OFFSET_CAPTURE );

  $introduce_before_header = substr($content, 0, $first_header_html[0][1] );
  $content = $introduce_before_header . $table_html . substr( $content, $first_header_html[0][1]);

  return $content;
}
add_filter( 'the_content', 'troms_table_content', 9999 );
