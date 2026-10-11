<?php

class Arreglofloral
{
    private $tbarreglofloralid;
    private $tbarreglofloralnombre;
    private $tbarreglofloraldescripcion;
    private $tbarreglofloralfoto;
    private $tbarreglofloralprecio;
    private $tbarreglofloralestado;

    public function __construct($datos = [])
    {
        $this->tbarreglofloralid          = $datos['tbarreglofloralid'] ?? null;
        $this->tbarreglofloralnombre      = $datos['tbarreglofloralnombre'] ?? '';
        $this->tbarreglofloraldescripcion = $datos['tbarreglofloraldescripcion'] ?? '';
        $this->tbarreglofloralfoto        = $datos['tbarreglofloralfoto'] ?? '';
        $this->tbarreglofloralprecio      = $datos['tbarreglofloralprecio'] ?? 0;
        $this->tbarreglofloralestado      = $datos['tbarreglofloralestado'] ?? 1;
    }

    public function getTbarreglofloralid() { 
        return $this->tbarreglofloralid; }

    public function setTbarreglofloralid($valor) { 
        $this->tbarreglofloralid = $valor; }

    public function getTbarreglofloralnombre() { 
        return $this->tbarreglofloralnombre; }

    public function setTbarreglofloralnombre($valor) { 
        $this->tbarreglofloralnombre = $valor; }

    public function getTbarreglofloraldescripcion() { 
        return $this->tbarreglofloraldescripcion; }

    public function setTbarreglofloraldescripcion($valor) { 
        $this->tbarreglofloraldescripcion = $valor; }

    public function getTbarreglofloralfoto() { 
        return $this->tbarreglofloralfoto; }

    public function setTbarreglofloralfoto($valor) { 
        $this->tbarreglofloralfoto = $valor; }

    public function getTbarreglofloralprecio() { 
        return $this->tbarreglofloralprecio; }

    public function setTbarreglofloralprecio($valor) { 
        $this->tbarreglofloralprecio = $valor; }

    public function getTbarreglofloralestado() { 
        return $this->tbarreglofloralestado; }

    public function setTbarreglofloralestado($valor) { 
        $this->tbarreglofloralestado = $valor; }

    public function estaActivo()
    {
        return (int) $this->tbarreglofloralestado === 1;
    }
}