<?php

// Spray para las flores: pintura floral, abrillantador de follaje o aromatizante floral.
// El color es de la pintura, el aroma del aromatizante y el cobro por flor (lo que se le cobra al
// cliente por cada flor pintada) solo de la pintura floral.
class Inventariocategoriaspray
{
    private $tbinventariocategoriasprayid;
    private $tbinventarioid;
    private $tbinventariocategoriaspraytipo;
    private $tbinventariocategoriaspraymarca;
    private $tbinventariocategoriaspraycolor;
    private $tbinventariocategoriasprayaroma;
    private $tbinventariocategoriaspraycontenido;
    private $tbinventariocategoriasprayfechavencimiento;
    private $tbinventariocategoriaspraycobroflor;

    public function __construct($datos = [])
    {
        $this->tbinventariocategoriasprayid               = $datos['tbinventariocategoriasprayid'] ?? null;
        $this->tbinventarioid                             = $datos['tbinventarioid'] ?? 0;
        $this->tbinventariocategoriaspraytipo             = $datos['tbinventariocategoriaspraytipo'] ?? '';
        $this->tbinventariocategoriaspraymarca            = $datos['tbinventariocategoriaspraymarca'] ?? '';
        $this->tbinventariocategoriaspraycolor            = $datos['tbinventariocategoriaspraycolor'] ?? null;
        $this->tbinventariocategoriasprayaroma            = $datos['tbinventariocategoriasprayaroma'] ?? null;
        $this->tbinventariocategoriaspraycontenido        = $datos['tbinventariocategoriaspraycontenido'] ?? 0;
        $this->tbinventariocategoriasprayfechavencimiento = $datos['tbinventariocategoriasprayfechavencimiento'] ?? null;
        $this->tbinventariocategoriaspraycobroflor        = $datos['tbinventariocategoriaspraycobroflor'] ?? null;
    }

    public function getTbinventariocategoriasprayid() {
        return $this->tbinventariocategoriasprayid; }

    public function setTbinventariocategoriasprayid($valor) {
        $this->tbinventariocategoriasprayid = $valor; }

    public function getTbinventarioid() {
        return $this->tbinventarioid; }

    public function setTbinventarioid($valor) {
        $this->tbinventarioid = $valor; }

    public function getTbinventariocategoriaspraytipo() {
        return $this->tbinventariocategoriaspraytipo; }

    public function setTbinventariocategoriaspraytipo($valor) {
        $this->tbinventariocategoriaspraytipo = $valor; }

    public function getTbinventariocategoriaspraymarca() {
        return $this->tbinventariocategoriaspraymarca; }

    public function setTbinventariocategoriaspraymarca($valor) {
        $this->tbinventariocategoriaspraymarca = $valor; }

    public function getTbinventariocategoriaspraycolor() {
        return $this->tbinventariocategoriaspraycolor; }

    public function setTbinventariocategoriaspraycolor($valor) {
        $this->tbinventariocategoriaspraycolor = $valor; }

    public function getTbinventariocategoriasprayaroma() {
        return $this->tbinventariocategoriasprayaroma; }

    public function setTbinventariocategoriasprayaroma($valor) {
        $this->tbinventariocategoriasprayaroma = $valor; }

    public function getTbinventariocategoriaspraycontenido() {
        return $this->tbinventariocategoriaspraycontenido; }

    public function setTbinventariocategoriaspraycontenido($valor) {
        $this->tbinventariocategoriaspraycontenido = $valor; }

    public function getTbinventariocategoriasprayfechavencimiento() {
        return $this->tbinventariocategoriasprayfechavencimiento; }

    public function setTbinventariocategoriasprayfechavencimiento($valor) {
        $this->tbinventariocategoriasprayfechavencimiento = $valor; }

    public function getTbinventariocategoriaspraycobroflor() {
        return $this->tbinventariocategoriaspraycobroflor; }

    public function setTbinventariocategoriaspraycobroflor($valor) {
        $this->tbinventariocategoriaspraycobroflor = $valor; }
}
