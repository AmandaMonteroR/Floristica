<?php

// Precio de venta por unidad de un producto del inventario en una temporada
class Inventariotemporada
{
    private $tbinventariotemporadaid;
    private $tbinventarioid;
    private $tbtemporadaid;
    private $tbinventariotemporadaprecioventa;

    public function __construct($datos = [])
    {
        $this->tbinventariotemporadaid          = $datos['tbinventariotemporadaid'] ?? null;
        $this->tbinventarioid                   = $datos['tbinventarioid'] ?? 0;
        $this->tbtemporadaid                    = $datos['tbtemporadaid'] ?? 0;
        $this->tbinventariotemporadaprecioventa = $datos['tbinventariotemporadaprecioventa'] ?? 0;
    }

    public function getTbinventariotemporadaid() {
        return $this->tbinventariotemporadaid; }

    public function setTbinventariotemporadaid($valor) {
        $this->tbinventariotemporadaid = $valor; }

    public function getTbinventarioid() {
        return $this->tbinventarioid; }

    public function setTbinventarioid($valor) {
        $this->tbinventarioid = $valor; }

    public function getTbtemporadaid() {
        return $this->tbtemporadaid; }

    public function setTbtemporadaid($valor) {
        $this->tbtemporadaid = $valor; }

    public function getTbinventariotemporadaprecioventa() {
        return $this->tbinventariotemporadaprecioventa; }

    public function setTbinventariotemporadaprecioventa($valor) {
        $this->tbinventariotemporadaprecioventa = $valor; }
}
