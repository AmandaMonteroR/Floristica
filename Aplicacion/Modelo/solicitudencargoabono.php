<?php

// Abono de la clienta a una solicitud (adelanto o pago del saldo)
class Solicitudencargoabono
{
    private $tbsolicitudencargoabonoid;
    private $tbsolicitudencargoid;
    private $tbsolicitudencargoabonofecha;
    private $tbsolicitudencargoabonomonto;
    private $tbsolicitudencargoabonometodo;
    private $tbsolicitudencargoabononota;
    private $tbempleadoid;

    public function __construct($datos = [])
    {
        $this->tbsolicitudencargoabonoid     = $datos['tbsolicitudencargoabonoid'] ?? null;
        $this->tbsolicitudencargoid          = $datos['tbsolicitudencargoid'] ?? 0;
        $this->tbsolicitudencargoabonofecha  = $datos['tbsolicitudencargoabonofecha'] ?? '';
        $this->tbsolicitudencargoabonomonto  = $datos['tbsolicitudencargoabonomonto'] ?? 0;
        $this->tbsolicitudencargoabonometodo = $datos['tbsolicitudencargoabonometodo'] ?? '';
        $this->tbsolicitudencargoabononota   = $datos['tbsolicitudencargoabononota'] ?? '';
        $this->tbempleadoid                  = $datos['tbempleadoid'] ?? 0;
    }

    public function getTbsolicitudencargoabonoid() {
        return $this->tbsolicitudencargoabonoid; }

    public function setTbsolicitudencargoabonoid($valor) {
        $this->tbsolicitudencargoabonoid = $valor; }

    public function getTbsolicitudencargoid() {
        return $this->tbsolicitudencargoid; }

    public function setTbsolicitudencargoid($valor) {
        $this->tbsolicitudencargoid = $valor; }

    public function getTbsolicitudencargoabonofecha() {
        return $this->tbsolicitudencargoabonofecha; }

    public function setTbsolicitudencargoabonofecha($valor) {
        $this->tbsolicitudencargoabonofecha = $valor; }

    public function getTbsolicitudencargoabonomonto() {
        return $this->tbsolicitudencargoabonomonto; }

    public function setTbsolicitudencargoabonomonto($valor) {
        $this->tbsolicitudencargoabonomonto = $valor; }

    public function getTbsolicitudencargoabonometodo() {
        return $this->tbsolicitudencargoabonometodo; }

    public function setTbsolicitudencargoabonometodo($valor) {
        $this->tbsolicitudencargoabonometodo = $valor; }

    public function getTbsolicitudencargoabononota() {
        return $this->tbsolicitudencargoabononota; }

    public function setTbsolicitudencargoabononota($valor) {
        $this->tbsolicitudencargoabononota = $valor; }

    public function getTbempleadoid() {
        return $this->tbempleadoid; }

    public function setTbempleadoid($valor) {
        $this->tbempleadoid = $valor; }
}
