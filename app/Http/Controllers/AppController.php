<?php

namespace App\Http\Controllers;

use App\Models\AppModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppController extends Controller
{
    //
    public function list($slug, $page = 1)
    {

        $term = DB::selectOne('SELECT b.count, a.name FROM `store_terms` a, `store_term_taxonomy` b WHERE a.term_id=b.term_id AND a.slug=  ?', [$slug]);

        $title = $term->name;
        $count = $term->count;

        // Get App Info
        $pageSize = 20;
        $title = "{$title} - 第{$page}页";

        if (!isset($page) || !is_numeric($page))
            $page = 1;
        if (($page - 1) * $pageSize > $count)
            $page = 0.9 + $count / $pageSize;
        $total_page = (int) (0.9 + $count / $pageSize);

        $slugs = [$slug];

        if($page<1)$page=1;
        $sql = "SELECT a.id, a.name, a.icon, a.version, a.size, a.author, a.description, a.addTime FROM store_app a
        WHERE id in
            (SELECT a.object_id FROM
                (SELECT `object_id` FROM store_terms b, store_term_relationships c, store_term_taxonomy d
                    WHERE c.term_taxonomy_id=d.term_taxonomy_id AND d.term_id=b.term_id AND b.slug='{$slugs[0]}' ";
        for($i=1; $i<count($slugs); $i++)
            $sql .= "AND object_id in (SELECT a.object_id FROM
                        (SELECT `object_id` FROM store_terms b, store_term_relationships c, store_term_taxonomy d WHERE c.term_taxonomy_id=d.term_taxonomy_id AND d.term_id=b.term_id AND b.slug='{$slugs[$i]}' ORDER BY `object_id` DESC LIMIT 0, 11 ) a
                        )";
        $sql .= "ORDER BY `object_id` DESC LIMIT " . (($page - 1) * $pageSize) . ", {$pageSize}
                ) a
            )";
        $appList = DB::select($sql);

        $sql = "SELECT a.term_id as term_id, a.name as name, a.slug as slug, b.taxonomy as taxonomy, b.term_taxonomy_id
        FROM store_terms a, store_term_taxonomy b WHERE a.term_id=b.term_id AND b.taxonomy!='category'";
        $result = DB::select($sql);
        $tags = array();
        foreach ($result as $row) {
            $tags[$row->taxonomy][] = array(
                'tag_id' => $row->term_id,
                'term_taxonomy_id' => $row->term_taxonomy_id,
                'tag_slug' => $row->slug,
                'tag_name' => $row->name
            );
        }

        return view('app', [
            'tags' => $tags,
            'slug' => $slug,
            'appList' => $appList,
            'total_page' => $total_page,
            'page' => $page,
            'term' =>  $term,
            'title' =>  $title,
            'count' =>  $count
            ]
        );
    }

    public function info(Request $request, $id){
        // header("content-type:application/json");

        $ret = DB::selectOne('SELECT * FROM `store_app` WHERE `id` = ?', [$id]);

        // 根据score降序
        $sql_tag_id = "SELECT b.term_id as tag_id, b.slug as slug, b.name as tag_name FROM store_app a, store_terms b, store_term_relationships c, store_term_taxonomy d
        WHERE a.id=c.object_id AND c.term_taxonomy_id=d.term_taxonomy_id AND d.term_id=b.term_id
        AND a.id='{$id}' AND d.taxonomy!='category' ORDER BY `c`.`term_score` DESC";
        $tags = array();
        $query = DB::select($sql_tag_id);
        foreach ($query as $row) {
            $tags[$row->slug] = $row->tag_name;
            if (count($tags) >= 5)
                break;
        }
        $ret->tags = $tags;

        $sql = "SELECT b.term_id as tag_id, b.slug as slug, b.name as name FROM store_app a, store_terms b, store_term_relationships c, store_term_taxonomy d
        WHERE a.id=c.object_id AND c.term_taxonomy_id=d.term_taxonomy_id AND d.term_id=b.term_id
        AND a.id='{$id}' AND d.taxonomy='category'";
        $term = DB::selectOne($sql);

        /*
        Array
        (
            [id] => 11474
            [version] => 101
            [name] => 计算器老虎版
            [author] => 老虎会游泳
            [description] => 计算器老虎会游泳开发，编译于2020.10.26
            [appId] => 30043
            [ch] => e6c1ec341
            [file_path] => mrpApp/计算器老虎版_52828a308400ea223128acc523e9b429.mrp
            [md5] => 52828a308400ea223128acc523e9b429
            [size] => 252666
            [addTime] => 1603815891
            [tags] => Array
                (
                )

        )
        */

        $request->session()->put('dowload_info', array(
            'id' => $id,
            'path' => '/' . $ret->file_path
        ));
        // var_dump($request->session()->all());

        $title = "{$ret->name} - 下载";

        $tags = array();
        $sql = "SELECT a.term_id as term_id, a.name as name, a.slug as slug, b.taxonomy as taxonomy, b.term_taxonomy_id
        FROM store_terms a, store_term_taxonomy b WHERE a.term_id=b.term_id AND b.taxonomy!='category'";
        $query = DB::select($sql);
        foreach ($query as $row) {
            $tags[$row->taxonomy][] = array(
                'tag_id' => $row->term_id,
                'term_taxonomy_id' => $row->term_taxonomy_id,
                'tag_slug' => $row->slug,
                'tag_name' => $row->name
            );
        }

        return view('info', [
            'term' => $term,
            'tags' => $tags,
            'title' => $title,
            'ret'=> $ret,
            'id' => $id
        ]);
    }
}
