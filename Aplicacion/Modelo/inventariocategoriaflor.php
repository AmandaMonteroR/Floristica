<?php

class Inventariocategoriaflor
{
    private $tbinventariocategoriaflorid;
    private $tbinventarioid;
    private $tbinventariocategoriaflortipo;
    private $tbinventariocategoriaflorcolor;
    private $tbinventariocategoriaflorlargotallo;
    private $tbinventariocategoriaflordiasduracion;

    public function __construct($datos = [])
    {
        $this->tbinventariocategoriaflorid           = $datos['tbinventariocategoriaflorid'] ?? null;
        $this->tbinventarioid                        = $datos['tbinventarioid'] ?? 0;
        $this->tbinventariocategoriaflortipo         = $datos['tbinventariocategoriaflortipo'] ?? '';
        $this->tbinventariocategoriaflorcolor        = $datos['tbinventariocategoriaflorcolor'] ?? '';
        $this->tbinventariocategoriaflorlargotallo   = $datos['tbinventariocategoriaflorlargotallo'] ?? 0;
        $this->tbinventariocategoriaflordiasduracion = $datos['tbinventariocategoriaflordiasduracion'] ?? 0;
    }

    public function getTbinventariocategoriaflorid() { 
        return $this->tbinventariocategoriaflorid; }

    public function setTbinventariocategoriaflorid($valor) { 
        $this->tbinventariocategoriaflorid = $valor; }

    public function getTbinventarioid() { 
        return $this->tbinventarioid; }

    public function setTbinventarioid($valor) { 
        $this->tbinventarioid = $valor; }

    public function getTbinventariocategoriaflortipo() { 
        return $this->tbinventariocategoriaflortipo; }

    public function setTbinventariocategoriaflortipo($valor) { 
        $this->tbinventariocategoriaflortipo = $valor; }

    public function getTbinventariocategoriaflorcolor() { 
        return $this->tbinventariocategoriaflorcolor; }

    public function setTbinventariocategoriaflorcolor($valor) { 
        $this->tbinventariocategoriaflorcolor = $valor; }

    public function getTbinventariocategoriaflorlargotallo() { 
        return $this->tbinventariocategoriaflorlargotallo; }

    public function setTbinventariocategoriaflorlargotallo($valor) { 
        $this->tbinventariocategoriaflorlargotallo = $valor; }

    public function getTbinventariocategoriaflordiasduracion() { 
        return $this->tbinventariocategoriaflordiasduracion; }

    public function setTbinventariocategoriaflordiasduracion($valor) { 
        $this->tbinventariocategoriaflordiasduracion = $valor; }
}