<?php

class Inventariocategoriapeluche
{
    private $tbinventariocategoriapelucheid;
    private $tbinventarioid;
    private $tbinventariocategoriapeluchetipo;
    private $tbinventariocategoriapeluchetamano;
    private $tbinventariocategoriapeluchecolor;
    private $tbinventariocategoriapeluchediseno;
    private $tbinventariocategoriapeluchematerial;

    public function __construct($datos = [])
    {
        $this->tbinventariocategoriapelucheid       = $datos['tbinventariocategoriapelucheid'] ?? null;
        $this->tbinventarioid                       = $datos['tbinventarioid'] ?? 0;
        $this->tbinventariocategoriapeluchetipo     = $datos['tbinventariocategoriapeluchetipo'] ?? '';
        $this->tbinventariocategoriapeluchetamano   = $datos['tbinventariocategoriapeluchetamano'] ?? 0;
        $this->tbinventariocategoriapeluchecolor    = $datos['tbinventariocategoriapeluchecolor'] ?? '';
        $this->tbinventariocategoriapeluchediseno   = $datos['tbinventariocategoriapeluchediseno'] ?? '';
        $this->tbinventariocategoriapeluchematerial = $datos['tbinventariocategoriapeluchematerial'] ?? '';
    }

    public function getTbinventariocategoriapelucheid() { 
        return $this->tbinventariocategoriapelucheid; }

    public function setTbinventariocategoriapelucheid($valor) { 
        $this->tbinventariocategoriapelucheid = $valor; }

    public function getTbinventarioid() { 
        return $this->tbinventarioid; }

    public function setTbinventarioid($valor) { 
        $this->tbinventarioid = $valor; }

    public function getTbinventariocategoriapeluchetipo() { 
        return $this->tbinventariocategoriapeluchetipo; }

    public function setTbinventariocategoriapeluchetipo($valor) { 
        $this->tbinventariocategoriapeluchetipo = $valor; }

    public function getTbinventariocategoriapeluchetamano() { 
        return $this->tbinventariocategoriapeluchetamano; }

    public function setTbinventariocategoriapeluchetamano($valor) { 
        $this->tbinventariocategoriapeluchetamano = $valor; }

    public function getTbinventariocategoriapeluchecolor() { 
        return $this->tbinventariocategoriapeluchecolor; }

    public function setTbinventariocategoriapeluchecolor($valor) { 
        $this->tbinventariocategoriapeluchecolor = $valor; }

    public function getTbinventariocategoriapeluchediseno() { 
        return $this->tbinventariocategoriapeluchediseno; }

    public function setTbinventariocategoriapeluchediseno($valor) { 
        $this->tbinventariocategoriapeluchediseno = $valor; }

    public function getTbinventariocategoriapeluchematerial() { 
        return $this->tbinventariocategoriapeluchematerial; }

    public function setTbinventariocategoriapeluchematerial($valor) { 
        $this->tbinventariocategoriapeluchematerial = $valor; }
}
