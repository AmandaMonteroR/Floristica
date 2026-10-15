<?php

class Inventariocategoriatarjeta
{
    private $tbinventariocategoriatarjetaid;
    private $tbinventarioid;
    private $tbinventariocategoriatarjetatipo;
    private $tbinventariocategoriatarjetatematica;
    private $tbinventariocategoriatarjetacolor;
    private $tbinventariocategoriatarjetamaterial;
    private $tbinventariocategoriatarjetamedida;
    private $tbinventariocategoriatarjetapersonalizable;
    private $tbinventariocategoriatarjetaelaboracion;

    public function __construct($datos = [])
    {
        $this->tbinventariocategoriatarjetaid             = $datos['tbinventariocategoriatarjetaid'] ?? null;
        $this->tbinventarioid                             = $datos['tbinventarioid'] ?? 0;
        $this->tbinventariocategoriatarjetatipo           = $datos['tbinventariocategoriatarjetatipo'] ?? '';
        $this->tbinventariocategoriatarjetatematica       = $datos['tbinventariocategoriatarjetatematica'] ?? '';
        $this->tbinventariocategoriatarjetacolor          = $datos['tbinventariocategoriatarjetacolor'] ?? '';
        $this->tbinventariocategoriatarjetamaterial       = $datos['tbinventariocategoriatarjetamaterial'] ?? '';
        $this->tbinventariocategoriatarjetamedida         = $datos['tbinventariocategoriatarjetamedida'] ?? '';
        $this->tbinventariocategoriatarjetapersonalizable = $datos['tbinventariocategoriatarjetapersonalizable'] ?? 0;
        $this->tbinventariocategoriatarjetaelaboracion    = $datos['tbinventariocategoriatarjetaelaboracion'] ?? '';
    }

    public function getTbinventariocategoriatarjetaid() { 
        return $this->tbinventariocategoriatarjetaid; }

    public function setTbinventariocategoriatarjetaid($valor) { 
        $this->tbinventariocategoriatarjetaid = $valor; }

    public function getTbinventarioid() { 
        return $this->tbinventarioid; }

    public function setTbinventarioid($valor) { 
        $this->tbinventarioid = $valor; }

    public function getTbinventariocategoriatarjetatipo() { 
        return $this->tbinventariocategoriatarjetatipo; }

    public function setTbinventariocategoriatarjetatipo($valor) { 
        $this->tbinventariocategoriatarjetatipo = $valor; }

    public function getTbinventariocategoriatarjetatematica() { 
        return $this->tbinventariocategoriatarjetatematica; }

    public function setTbinventariocategoriatarjetatematica($valor) { 
        $this->tbinventariocategoriatarjetatematica = $valor; }

    public function getTbinventariocategoriatarjetacolor() { 
        return $this->tbinventariocategoriatarjetacolor; }

    public function setTbinventariocategoriatarjetacolor($valor) { 
        $this->tbinventariocategoriatarjetacolor = $valor; }

    public function getTbinventariocategoriatarjetamaterial() { 
        return $this->tbinventariocategoriatarjetamaterial; }

    public function setTbinventariocategoriatarjetamaterial($valor) { 
        $this->tbinventariocategoriatarjetamaterial = $valor; }

    public function getTbinventariocategoriatarjetamedida() { 
        return $this->tbinventariocategoriatarjetamedida; }

    public function setTbinventariocategoriatarjetamedida($valor) { 
        $this->tbinventariocategoriatarjetamedida = $valor; }

    public function getTbinventariocategoriatarjetapersonalizable() { 
        return $this->tbinventariocategoriatarjetapersonalizable; }

    public function setTbinventariocategoriatarjetapersonalizable($valor) { 
        $this->tbinventariocategoriatarjetapersonalizable = $valor; }

    public function getTbinventariocategoriatarjetaelaboracion() { 
        return $this->tbinventariocategoriatarjetaelaboracion; }

    public function setTbinventariocategoriatarjetaelaboracion($valor) { 
        $this->tbinventariocategoriatarjetaelaboracion = $valor; }
}
