<?php

class Inventariocategoriabase
{
    private $tbinventariocategoriabaseid;
    private $tbinventarioid;
    private $tbinventariocategoriabasetipo;
    private $tbinventariocategoriabasematerial;
    private $tbinventariocategoriabasecolor;
    private $tbinventariocategoriabaseforma;
    private $tbinventariocategoriabasemedida;

    public function __construct($datos = [])
    {
        $this->tbinventariocategoriabaseid       = $datos['tbinventariocategoriabaseid'] ?? null;
        $this->tbinventarioid                    = $datos['tbinventarioid'] ?? 0;
        $this->tbinventariocategoriabasetipo     = $datos['tbinventariocategoriabasetipo'] ?? '';
        $this->tbinventariocategoriabasematerial = $datos['tbinventariocategoriabasematerial'] ?? '';
        $this->tbinventariocategoriabasecolor    = $datos['tbinventariocategoriabasecolor'] ?? '';
        $this->tbinventariocategoriabaseforma    = $datos['tbinventariocategoriabaseforma'] ?? '';
        $this->tbinventariocategoriabasemedida   = $datos['tbinventariocategoriabasemedida'] ?? '';
    }

    public function getTbinventariocategoriabaseid() { 
        return $this->tbinventariocategoriabaseid; }

    public function setTbinventariocategoriabaseid($valor) { 
        $this->tbinventariocategoriabaseid = $valor; }

    public function getTbinventarioid() { 
        return $this->tbinventarioid; }

    public function setTbinventarioid($valor) { 
        $this->tbinventarioid = $valor; }

    public function getTbinventariocategoriabasetipo() { 
        return $this->tbinventariocategoriabasetipo; }

    public function setTbinventariocategoriabasetipo($valor) { 
        $this->tbinventariocategoriabasetipo = $valor; }

    public function getTbinventariocategoriabasematerial() { 
        return $this->tbinventariocategoriabasematerial; }

    public function setTbinventariocategoriabasematerial($valor) { 
        $this->tbinventariocategoriabasematerial = $valor; }

    public function getTbinventariocategoriabasecolor() { 
        return $this->tbinventariocategoriabasecolor; }

    public function setTbinventariocategoriabasecolor($valor) { 
        $this->tbinventariocategoriabasecolor = $valor; }

    public function getTbinventariocategoriabaseforma() { 
        return $this->tbinventariocategoriabaseforma; }

    public function setTbinventariocategoriabaseforma($valor) { 
        $this->tbinventariocategoriabaseforma = $valor; }

    public function getTbinventariocategoriabasemedida() { 
        return $this->tbinventariocategoriabasemedida; }

    public function setTbinventariocategoriabasemedida($valor) { 
        $this->tbinventariocategoriabasemedida = $valor; }
}