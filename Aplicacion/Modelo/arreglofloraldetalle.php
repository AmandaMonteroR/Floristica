<?php

class Arreglofloraldetalle
{
    private $tbarreglofloraldetalleid;
    private $tbarreglofloralid;
    private $tbinventarioid;
    private $tbarreglofloraldetallecantidad;

    public function __construct($datos = [])
    {
        $this->tbarreglofloraldetalleid       = $datos['tbarreglofloraldetalleid'] ?? null;
        $this->tbarreglofloralid              = $datos['tbarreglofloralid'] ?? 0;
        $this->tbinventarioid                 = $datos['tbinventarioid'] ?? 0;
        $this->tbarreglofloraldetallecantidad = $datos['tbarreglofloraldetallecantidad'] ?? 0;
    }

    public function getTbarreglofloraldetalleid() { 
        return $this->tbarreglofloraldetalleid; }

    public function setTbarreglofloraldetalleid($valor) { 
        $this->tbarreglofloraldetalleid = $valor; }

    public function getTbarreglofloralid() { 
        return $this->tbarreglofloralid; }

    public function setTbarreglofloralid($valor) { 
        $this->tbarreglofloralid = $valor; }

    public function getTbinventarioid() { 
        return $this->tbinventarioid; }

    public function setTbinventarioid($valor) { 
        $this->tbinventarioid = $valor; }

    public function getTbarreglofloraldetallecantidad() { 
        return $this->tbarreglofloraldetallecantidad; }

    public function setTbarreglofloraldetallecantidad($valor) { 
        $this->tbarreglofloraldetallecantidad = $valor; }
}