<?php

// Color del catálogo de colores (se usa en los productos del inventario)
class Color
{
    private $tbcolorid;
    private $tbcolornombre;
    private $tbcolorestado;

    public function __construct($datos = [])
    {
        $this->tbcolorid     = $datos['tbcolorid'] ?? null;
        $this->tbcolornombre = $datos['tbcolornombre'] ?? '';
        $this->tbcolorestado = $datos['tbcolorestado'] ?? 1;
    }

    public function getTbcolorid() {
        return $this->tbcolorid; }

    public function setTbcolorid($valor) {
        $this->tbcolorid = $valor; }

    public function getTbcolornombre() {
        return $this->tbcolornombre; }

    public function setTbcolornombre($valor) {
        $this->tbcolornombre = $valor; }

    public function getTbcolorestado() {
        return $this->tbcolorestado; }

    public function setTbcolorestado($valor) {
        $this->tbcolorestado = $valor; }
}
