<?php

// Otro proveedor de un insumo: el mismo paquete también se le puede comprar a este proveedor, a su precio
class Inventariootroproveedor
{
    private $tbinventariootroproveedorid;
    private $tbinventarioid;
    private $tbproveedorid;
    private $tbinventariootroproveedorpreciopaquete;

    public function __construct($datos = [])
    {
        $this->tbinventariootroproveedorid            = $datos['tbinventariootroproveedorid'] ?? null;
        $this->tbinventarioid                         = $datos['tbinventarioid'] ?? 0;
        $this->tbproveedorid                          = $datos['tbproveedorid'] ?? 0;
        $this->tbinventariootroproveedorpreciopaquete = $datos['tbinventariootroproveedorpreciopaquete'] ?? 0;
    }

    public function getTbinventariootroproveedorid() {
        return $this->tbinventariootroproveedorid; }

    public function setTbinventariootroproveedorid($valor) {
        $this->tbinventariootroproveedorid = $valor; }

    public function getTbinventarioid() {
        return $this->tbinventarioid; }

    public function setTbinventarioid($valor) {
        $this->tbinventarioid = $valor; }

    public function getTbproveedorid() {
        return $this->tbproveedorid; }

    public function setTbproveedorid($valor) {
        $this->tbproveedorid = $valor; }

    public function getTbinventariootroproveedorpreciopaquete() {
        return $this->tbinventariootroproveedorpreciopaquete; }

    public function setTbinventariootroproveedorpreciopaquete($valor) {
        $this->tbinventariootroproveedorpreciopaquete = $valor; }
}
