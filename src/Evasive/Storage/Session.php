<?php
namespace Evasive\Storage;

class Session implements StorageInterface
{

    const SESSION_NAMESPACE = 'phpevasive';
    
    public function get()
    {
        $session = new $_SESSION[self::SESSION_NAMESPACE];
        
        if (! empty($session)) {
            return $session['data'];
        }
        
        return null;
    }

    public function store($data)
    {
        $session = new $_SESSION[self::SESSION_NAMESPACE];

        $session['data'] = $data;
        
        session_write_close();
        session_start();
    }

    public function update($data)
    {
        $session = new $_SESSION[self::SESSION_NAMESPACE];
                
        foreach ($data as $key => $value) {
            $session['data'][$key] = $value;
        }

        session_write_close();
        session_start();
    }
}