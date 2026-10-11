<?php

class Inventariocategoriaextra
{
    private $tbinventariocategoriaextraid;
    private $tbinventarioid;
    private $tbinventariocategoriaextratipo;
    private $tbinventariocategoriaextramarca;
    private $tbinventariocategoriaextratamano;
    private $tbinventariocategoriaextracolor;
    private $tbinventariocategoriaextracontenido;
    private $tbinventariocategoriaextrafechavencimiento;

    public function __construct($datos = [])
    {
        $this->tbinventariocategoriaextraid               = $datos['tbinventariocategoriaextraid'] ?? null;
        $this->tbinventarioid                             = $datos['tbinventarioid'] ?? 0;
        $this->tbinventariocategoriaextratipo             = $datos['tbinventariocategoriaextratipo'] ?? '';
        $this->tbinventariocategoriaextramarca            = $datos['tbinventariocategoriaextramarca'] ?? '';
        $this->tbinventariocategoriaextratamano           = $datos['tbinventariocategoriaextratamano'] ?? '';
        $this->tbinventariocategoriaextracolor            = $datos['tbinventariocategoriaextracolor'] ?? '';
        $this->tbinventariocategoriaextracontenido        = $datos['tbinventariocategoriaextracontenido'] ?? '';
        $this->tbinventariocategoriaextrafechavencimiento = $datos['tbinventariocategoriaextrafechavencimiento'] ?? null;
    }

    public function getTbinventariocategoriaextraid() { 
        return $this->tbinventariocategoriaextraid; }

    public function setTbinventariocategoriaextraid($valor) { 
        $this->tbinventariocategoriaextraid = $valor; }

    public function getTbinventarioid() { 
        return $this->tbinventarioid; }

    public function setTbinventarioid($valor) { 
        $this->tbinventarioid = $valor; }

    public function getTbinventariocategoriaextratipo() { 
        return $this->tbinventariocategoriaextratipo; }

    public function setTbinventariocategoriaextratipo($valor) { 
        $this->tbinventariocategoriaextratipo = $valor; }

    public function getTbinventariocategoriaextramarca() { 
        return $this->tbinventariocategoriaextramarca; }

    public function setTbinventariocategoriaextramarca($valor) { 
        $this->tbinventariocategoriaextramarca = $valor; }

    public function getTbinventariocategoriaextratamano() { 
        return $this->tbinventariocategoriaextratamano; }

    public function setTbinventariocategoriaextratamano($valor) { 
        $this->tbinventariocategoriaextratamano = $valor; }

    public function getTbinventariocategoriaextracolor() { 
        return $this->tbinventariocategoriaextracolor; }

    public function setTbinventariocategoriaextracolor($valor) { 
        $this->tbinventariocategoriaextracolor = $valor; }

    public function getTbinventariocategoriaextracontenido() { 
        return $this->tbinventariocategoriaextracontenido; }

    public function setTbinventariocategoriaextracontenido($valor) { 
        $this->tbinventariocategoriaextracontenido = $valor; }

    public function getTbinventariocategoriaextrafechavencimiento() { 
        return $this->tbinventariocategoriaextrafechavencimiento; }

    public function setTbinventariocategoriaextrafechavencimiento($valor) { 
        $this->tbinventariocategoriaextrafechavencimiento = $valor; }
}