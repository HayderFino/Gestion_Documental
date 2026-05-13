<?php

namespace app\helpers;

class JsonDB {
    private $path;

    public function __construct($file) {
        $this->path = ROOT_PATH . "/database/data/" . $file . ".json";
    }

    public function all() {
        if (!file_exists($this->path)) return [];
        $data = file_get_contents($this->path);
        return json_decode($data, true) ?: [];
    }

    public function find($id) {
        $data = $this->all();
        foreach ($data as $item) {
            if ($item['id'] == $id) return $item;
        }
        return null;
    }

    public function create($newItem) {
        $data = $this->all();
        $newItem['id'] = count($data) > 0 ? max(array_column($data, 'id')) + 1 : 1;
        $newItem['created_at'] = date('Y-m-d H:i:s');
        $data[] = $newItem;
        $this->save($data);
        return $newItem['id'];
    }

    public function update($id, $updatedItem) {
        $data = $this->all();
        foreach ($data as &$item) {
            if ($item['id'] == $id) {
                $item = array_merge($item, $updatedItem);
                break;
            }
        }
        $this->save($data);
        return true;
    }

    public function save($data) {
        file_put_contents($this->path, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    public function where($field, $value) {
        $data = $this->all();
        return array_filter($data, function($item) use ($field, $value) {
            return isset($item[$field]) && $item[$field] == $value;
        });
    }
}
