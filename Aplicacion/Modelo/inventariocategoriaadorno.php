<?php

class Inventariocategoriaadorno
{
    private $tbinventariocategoriaadornoid;
    private $tbinventarioid;
    private $tbinventariocategoriaadornotipo;
    private $tbinventariocategoriaadornomaterial;
    private $tbinventariocategoriaadornocolor;
    private $tbinventariocategoriaadornomedida;
    private $tbinventariocategoriaadornodiseno;

    public function __construct($datos = [])
    {
        $this->tbinventariocategoriaadornoid       = $datos['tbinventariocategoriaadornoid'] ?? null;
        $this->tbinventarioid                      = $datos['tbinventarioid'] ?? 0;
        $this->tbinventariocategoriaadornotipo     = $datos['tbinventariocategoriaadornotipo'] ?? '';
        $this->tbinventariocategoriaadornomaterial = $datos['tbinventariocategoriaadornomaterial'] ?? '';
        $this->tbinventariocategoriaadornocolor    = $datos['tbinventariocategoriaadornocolor'] ?? '';
        $this->tbinventariocategoriaadornomedida   = $datos['tbinventariocategoriaadornomedida'] ?? '';
        $this->tbinventariocategoriaadornodiseno   = $datos['tbinventariocategoriaadornodiseno'] ?? '';
    }

    public function getTbinventariocategoriaadornoid() { 
        return $this->tbinventariocategoriaadornoid; }

    public function setTbinventariocategoriaadornoid($valor) { 
        $this->tbinventariocategoriaadornoid = $valor; }

    public function getTbinventarioid() { 
        return $this->tbinventarioid; }

    public function setTbinventarioid($valor) { 
        $this->tbinventarioid = $valor; }

    public function getTbinventariocategoriaadornotipo() { 
        return $this->tbinventariocategoriaadornotipo; }

    public function setTbinventariocategoriaadornotipo($valor) { 
        $this->tbinventariocategoriaadornotipo = $valor; }

    public function getTbinventariocategoriaadornomaterial() { 
        return $this->tbinventariocategoriaadornomaterial; }

    public function setTbinventariocategoriaadornomaterial($valor) { 
        $this->tbinventariocategoriaadornomaterial = $valor; }

    public function getTbinventariocategoriaadornocolor() { 
        return $this->tbinventariocategoriaadornocolor; }

    public function setTbinventariocategoriaadornocolor($valor) { 
        $this->tbinventariocategoriaadornocolor = $valor; }

    public function getTbinventariocategoriaadornomedida() { 
        return $this->tbinventariocategoriaadornomedida; }

    public function setTbinventariocategoriaadornomedida($valor) { 
        $this->tbinventariocategoriaadornomedida = $valor; }

    public function getTbinventariocategoriaadornodiseno() { 
        return $this->tbinventariocategoriaadornodiseno; }

    public function setTbinventariocategoriaadornodiseno($valor) { 
        $this->tbinventariocategoriaadornodiseno = $valor; }
}