<?php

// Precio de un arreglo del catálogo en una temporada
class Arreglofloraltemporada
{
    private $tbarreglofloraltemporadaid;
    private $tbarreglofloralid;
    private $tbtemporadaid;
    private $tbarreglofloraltemporadaprecio;

    public function __construct($datos = [])
    {
        $this->tbarreglofloraltemporadaid     = $datos['tbarreglofloraltemporadaid'] ?? null;
        $this->tbarreglofloralid              = $datos['tbarreglofloralid'] ?? 0;
        $this->tbtemporadaid                  = $datos['tbtemporadaid'] ?? 0;
        $this->tbarreglofloraltemporadaprecio = $datos['tbarreglofloraltemporadaprecio'] ?? 0;
    }

    public function getTbarreglofloraltemporadaid() {
        return $this->tbarreglofloraltemporadaid; }

    public function setTbarreglofloraltemporadaid($valor) {
        $this->tbarreglofloraltemporadaid = $valor; }

    public function getTbarreglofloralid() {
        return $this->tbarreglofloralid; }

    public function setTbarreglofloralid($valor) {
        $this->tbarreglofloralid = $valor; }

    public function getTbtemporadaid() {
        return $this->tbtemporadaid; }

    public function setTbtemporadaid($valor) {
        $this->tbtemporadaid = $valor; }

    public function getTbarreglofloraltemporadaprecio() {
        return $this->tbarreglofloraltemporadaprecio; }

    public function setTbarreglofloraltemporadaprecio($valor) {
        $this->tbarreglofloraltemporadaprecio = $valor; }
}
