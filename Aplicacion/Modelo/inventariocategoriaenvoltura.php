<?php

class Inventariocategoriaenvoltura
{
    private $tbinventariocategoriaenvolturaid;
    private $tbinventarioid;
    private $tbinventariocategoriaenvolturaclase;
    private $tbinventariocategoriaenvolturatipo;
    private $tbinventariocategoriaenvolturacolor;
    private $tbinventariocategoriaenvolturadiseno;
    private $tbinventariocategoriaenvolturamedida;
    private $tbinventariocategoriaenvolturamaterial;
    private $tbinventariocategoriaenvolturaacabado;
    private $tbinventariocategoriaenvolturapersonalizable;

    public function __construct($datos = [])
    {
        $this->tbinventariocategoriaenvolturaid             = $datos['tbinventariocategoriaenvolturaid'] ?? null;
        $this->tbinventarioid                               = $datos['tbinventarioid'] ?? 0;
        $this->tbinventariocategoriaenvolturaclase          = $datos['tbinventariocategoriaenvolturaclase'] ?? '';
        $this->tbinventariocategoriaenvolturatipo           = $datos['tbinventariocategoriaenvolturatipo'] ?? '';
        $this->tbinventariocategoriaenvolturacolor          = $datos['tbinventariocategoriaenvolturacolor'] ?? '';
        $this->tbinventariocategoriaenvolturadiseno         = $datos['tbinventariocategoriaenvolturadiseno'] ?? '';
        $this->tbinventariocategoriaenvolturamedida         = $datos['tbinventariocategoriaenvolturamedida'] ?? '';
        $this->tbinventariocategoriaenvolturamaterial       = $datos['tbinventariocategoriaenvolturamaterial'] ?? '';
        $this->tbinventariocategoriaenvolturaacabado        = $datos['tbinventariocategoriaenvolturaacabado'] ?? null;
        $this->tbinventariocategoriaenvolturapersonalizable = $datos['tbinventariocategoriaenvolturapersonalizable'] ?? null;
    }

    public function getTbinventariocategoriaenvolturaid() { 
        return $this->tbinventariocategoriaenvolturaid; }

    public function setTbinventariocategoriaenvolturaid($valor) { 
        $this->tbinventariocategoriaenvolturaid = $valor; }

    public function getTbinventarioid() { 
        return $this->tbinventarioid; }

    public function setTbinventarioid($valor) { 
        $this->tbinventarioid = $valor; }

    public function getTbinventariocategoriaenvolturaclase() { 
        return $this->tbinventariocategoriaenvolturaclase; }

    public function setTbinventariocategoriaenvolturaclase($valor) { 
        $this->tbinventariocategoriaenvolturaclase = $valor; }

    public function getTbinventariocategoriaenvolturatipo() { 
        return $this->tbinventariocategoriaenvolturatipo; }

    public function setTbinventariocategoriaenvolturatipo($valor) { 
        $this->tbinventariocategoriaenvolturatipo = $valor; }

    public function getTbinventariocategoriaenvolturacolor() { 
        return $this->tbinventariocategoriaenvolturacolor; }

    public function setTbinventariocategoriaenvolturacolor($valor) { 
        $this->tbinventariocategoriaenvolturacolor = $valor; }

    public function getTbinventariocategoriaenvolturadiseno() { 
        return $this->tbinventariocategoriaenvolturadiseno; }

    public function setTbinventariocategoriaenvolturadiseno($valor) { 
        $this->tbinventariocategoriaenvolturadiseno = $valor; }

    public function getTbinventariocategoriaenvolturamedida() { 
        return $this->tbinventariocategoriaenvolturamedida; }

    public function setTbinventariocategoriaenvolturamedida($valor) { 
        $this->tbinventariocategoriaenvolturamedida = $valor; }

    public function getTbinventariocategoriaenvolturamaterial() { 
        return $this->tbinventariocategoriaenvolturamaterial; }

    public function setTbinventariocategoriaenvolturamaterial($valor) { 
        $this->tbinventariocategoriaenvolturamaterial = $valor; }

    public function getTbinventariocategoriaenvolturaacabado() { 
        return $this->tbinventariocategoriaenvolturaacabado; }

    public function setTbinventariocategoriaenvolturaacabado($valor) { 
        $this->tbinventariocategoriaenvolturaacabado = $valor; }

    public function getTbinventariocategoriaenvolturapersonalizable() { 
        return $this->tbinventariocategoriaenvolturapersonalizable; }

    public function setTbinventariocategoriaenvolturapersonalizable($valor) { 
        $this->tbinventariocategoriaenvolturapersonalizable = $valor; }
}
