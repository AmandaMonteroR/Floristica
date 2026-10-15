<?php

// Producto de una solicitud: adicional de un arreglo predefinido o parte de un arreglo personalizado.
// Guarda el precio de venta que tenía el producto cuando se registró la solicitud.
class Solicitudencargoinsumo
{
    private $tbsolicitudencargoinsumoid;
    private $tbsolicitudencargoid;
    private $tbinventarioid;
    private $tbsolicitudencargoinsumocantidad;
    private $tbsolicitudencargoinsumopreciounitario;

    public function __construct($datos = [])
    {
        $this->tbsolicitudencargoinsumoid             = $datos['tbsolicitudencargoinsumoid'] ?? null;
        $this->tbsolicitudencargoid                   = $datos['tbsolicitudencargoid'] ?? 0;
        $this->tbinventarioid                         = $datos['tbinventarioid'] ?? 0;
        $this->tbsolicitudencargoinsumocantidad       = $datos['tbsolicitudencargoinsumocantidad'] ?? 0;
        $this->tbsolicitudencargoinsumopreciounitario = $datos['tbsolicitudencargoinsumopreciounitario'] ?? 0;
    }

    public function getTbsolicitudencargoinsumoid() {
        return $this->tbsolicitudencargoinsumoid; }

    public function setTbsolicitudencargoinsumoid($valor) {
        $this->tbsolicitudencargoinsumoid = $valor; }

    public function getTbsolicitudencargoid() {
        return $this->tbsolicitudencargoid; }

    public function setTbsolicitudencargoid($valor) {
        $this->tbsolicitudencargoid = $valor; }

    public function getTbinventarioid() {
        return $this->tbinventarioid; }

    public function setTbinventarioid($valor) {
        $this->tbinventarioid = $valor; }

    public function getTbsolicitudencargoinsumocantidad() {
        return $this->tbsolicitudencargoinsumocantidad; }

    public function setTbsolicitudencargoinsumocantidad($valor) {
        $this->tbsolicitudencargoinsumocantidad = $valor; }

    public function getTbsolicitudencargoinsumopreciounitario() {
        return $this->tbsolicitudencargoinsumopreciounitario; }

    public function setTbsolicitudencargoinsumopreciounitario($valor) {
        $this->tbsolicitudencargoinsumopreciounitario = $valor; }
}
