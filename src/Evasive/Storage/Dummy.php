<?php
namespace Evasive\Storage;

class Dummy implements StorageInterface
{

    
    public function get()
    {
        return null;
    }

    public function store($data)
    {
       
    }

    public function update($data)
    {
        
    }
}