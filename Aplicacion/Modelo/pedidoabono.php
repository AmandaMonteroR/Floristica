<?php

// Abono a un pedido comprado a crédito (lo que se le va pagando al proveedor)
class Pedidoabono
{
    private $tbpedidoabonoid;
    private $tbpedidoid;
    private $tbpedidoabonofecha;
    private $tbpedidoabonomonto;
    private $tbpedidoabonometodo;
    private $tbpedidoabononota;
    private $tbempleadoid;

    public function __construct($datos = [])
    {
        $this->tbpedidoabonoid     = $datos['tbpedidoabonoid'] ?? null;
        $this->tbpedidoid          = $datos['tbpedidoid'] ?? 0;
        $this->tbpedidoabonofecha  = $datos['tbpedidoabonofecha'] ?? '';
        $this->tbpedidoabonomonto  = $datos['tbpedidoabonomonto'] ?? 0;
        $this->tbpedidoabonometodo = $datos['tbpedidoabonometodo'] ?? '';
        $this->tbpedidoabononota   = $datos['tbpedidoabononota'] ?? '';
        $this->tbempleadoid        = $datos['tbempleadoid'] ?? 0;
    }

    public function getTbpedidoabonoid() {
        return $this->tbpedidoabonoid; }

    public function setTbpedidoabonoid($valor) {
        $this->tbpedidoabonoid = $valor; }

    public function getTbpedidoid() {
        return $this->tbpedidoid; }

    public function setTbpedidoid($valor) {
        $this->tbpedidoid = $valor; }

    public function getTbpedidoabonofecha() {
        return $this->tbpedidoabonofecha; }

    public function setTbpedidoabonofecha($valor) {
        $this->tbpedidoabonofecha = $valor; }

    public function getTbpedidoabonomonto() {
        return $this->tbpedidoabonomonto; }

    public function setTbpedidoabonomonto($valor) {
        $this->tbpedidoabonomonto = $valor; }

    public function getTbpedidoabonometodo() {
        return $this->tbpedidoabonometodo; }

    public function setTbpedidoabonometodo($valor) {
        $this->tbpedidoabonometodo = $valor; }

    public function getTbpedidoabononota() {
        return $this->tbpedidoabononota; }

    public function setTbpedidoabononota($valor) {
        $this->tbpedidoabononota = $valor; }

    public function getTbempleadoid() {
        return $this->tbempleadoid; }

    public function setTbempleadoid($valor) {
        $this->tbempleadoid = $valor; }
}
