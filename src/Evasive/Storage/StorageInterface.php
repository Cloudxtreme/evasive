<?php
namespace Evasive\Storage;

interface StorageInterface {
   
    public function store($data);
    
    public function update($data);
}