<?php

trait emptyTrait {}

trait nonEmptyTrait{
    public function y() {}
}

trait nonEmptyTrait2 {
    use emptyTrait;
}

?>