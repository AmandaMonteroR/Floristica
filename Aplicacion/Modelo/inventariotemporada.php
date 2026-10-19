<?php

// Precio del paquete de un producto del inventario en una temporada (lo que cobra el proveedor)
class Inventariotemporada
{
    private $tbinventariotemporadaid;
    private $tbinventarioid;
    private $tbtemporadaid;
    private $tbinventariotemporadapreciopaquete;

    public function __construct($datos = [])
    {
        $this->tbinventariotemporadaid            = $datos['tbinventariotemporadaid'] ?? null;
        $this->tbinventarioid                     = $datos['tbinventarioid'] ?? 0;
        $this->tbtemporadaid                      = $datos['tbtemporadaid'] ?? 0;
        $this->tbinventariotemporadapreciopaquete = $datos['tbinventariotemporadapreciopaquete'] ?? 0;
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

    public function getTbinventariotemporadapreciopaquete() {
        return $this->tbinventariotemporadapreciopaquete; }

    public function setTbinventariotemporadapreciopaquete($valor) {
        $this->tbinventariotemporadapreciopaquete = $valor; }
}
