<?php

namespace block_user_manager;

use moodle_url, html_writer;

class html
{
    /**
     * Формирует и возвращает маркированны html список (ul) с требуемыми сылками.
     * @param string[] $links - массив ссылок.
     * @param array[] $linksparams - массив массивов параметров для ссылок $links.
     * @param string $langfile - название языкового файла.
     * @param string[] $keyslangfile - ключи в языковом файле (названия ссылок).
     * @return string html строка.
     * */
    public static function generate_html_list_links(array $links = [], array $linksparams = [], string $langfile = '', array $keyslangfile = []): string
    {
        $html_str = '<ul style="list-style: none; margin-left: -15px;">';
        foreach ($links as $key => $link) {
            $html_str .= '<li>';
            if (count($linksparams))
                $url = new moodle_url($link, $linksparams[$key]);
            else
                $url = new moodle_url($link);

            $html_str .= html_writer::link($url, get_string($keyslangfile[$key], $langfile));
            $html_str .= '</li>';
        }
        $html_str .= '</ul>';

        return $html_str;
    }

    public static function generate_paragraph_list_from_arr(array $arr): string
    {
        $html_str = '';

        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $html_str .= "<p style='margin-bottom: 0;'><b>$key</b>: ";
                for ($i = 0; $i < count($value); $i++) {
                    $html_str .= $value[$i];
                    if ($i < count($value) - 1) $html_str .= ', ';
                }
                $html_str .= "</p>";
            } else {
                $html_str .= "<p style='margin-bottom: 0;'><b>$key</b>: $value</p>";
            }
        }

        return $html_str;
    }

    public static function generate_label_with_html(string $label = '', string $html = ''): string {
        return '
            <div id="fitem_id_fullname" class="form-group row  fitem">
                <div class="col-md-3">
                    <span class="float-sm-right text-nowrap"></span>
                    <span class="col-form-label d-inline">'.$label.' </span>
                </div>
                <div class="col-md-9 form-inline felement" data-fieldtype="static">
                    <div class="form-control-static">'.$html.'</div>
                </div>
            </div>
        ';
    }
}