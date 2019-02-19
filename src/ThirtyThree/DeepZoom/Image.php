<?php

namespace ThirtyThree\DeepZoom;

use InvalidArgumentException;

class Image
{
    protected $width;
    protected $height;
    protected $tileSize = 256;
    protected $overlap = 1;
    protected $format = 'jpeg';

    protected $parent = null;

    // calculated
    protected $maxLevel;
    protected $scale = 1; // relative to parent

    public function __construct($width, $height, array $optional = [])
    {
        $this->width = $width;
        $this->height = $height;

        if (! empty($optional['tileSize'])) {
            $this->tileSize = $optional['tileSize'];
        }
        if (! empty($optional['overlap'])) {
            $this->overlap = $optional['overlap'];
        }
        if (! empty($optional['format'])) {
            $this->format = $optional['format'];
        }
        if (! empty($optional['parent']) && $optional['parent'] instanceof self) {
            $this->parent = $optional['parent'];
            $this->scale = pow(0.5, $this->parent->maxLevel() - $this->maxLevel());
        }
    }

    public function width()
    {
        return $this->width;
    }

    public function height()
    {
        return $this->height;
    }

    public function tileSize()
    {
        return $this->tileSize;
    }

    public function overlap()
    {
        return $this->overlap;
    }

    public function scale()
    {
        return $this->scale;
    }

    /**
     * 最大等级.
     *
     * @return int
     */
    public function maxLevel()
    {
        if (! empty($this->maxLevel)) {
            return $this->maxLevel;
        }
        $maxLevel = (int) ceil(log(max($this->width(), $this->height()), 2));
        $this->maxLevel = $maxLevel;

        return $maxLevel;
    }

    /**
     * 块数.
     *
     * @return array
     */
    public function numberTiles()
    {
        $columns = (int) ceil(floatval($this->width()) / $this->tileSize());
        $rows = (int) ceil(floatval($this->height()) / $this->tileSize());

        return ['columns' => $columns, 'rows' => $rows];
    }

    /**
     * 所有的块.
     *
     * @return \Generator
     */
    public function tiles()
    {
        $number = $this->numberTiles();
        for ($row = 0; $row < $number['rows']; $row++) {
            for ($column = 0; $column < $number['columns']; $column++) {
                $file = $column.'_'.$row.'.'.$this->format;
                $bounds = $this->tileBounds($column, $row);
                yield $file => compact('file', 'column', 'row', 'bounds');
            }
        }
    }

    /**
     * 根据等级进行裁剪.
     *
     * @param $level
     *
     * @return DeepZoomImage
     */
    public function cropByLevel($level)
    {
        if ($level < 0 || $level > $this->maxLevel()) {
            throw new InvalidArgumentException('level should between 0 and '.$this->maxLevel());
        }
        if ($this->maxLevel() == $level) {
            return clone $this;
        }

        $scale = pow(0.5, $this->maxLevel() - $level);
        $width = (int) ceil($this->width() * $scale);
        $height = (int) ceil($this->height() * $scale);

        return new self($width, $height, [
            'tileSize' => $this->tileSize(),
            'overlap' => $this->overlap(),
            'format' => $this->format,
            'parent' => $this,
        ]);
    }

    /**
     * 每一块的位置和大小.
     *
     * @param $column
     * @param $row
     *
     * @return array
     */
    protected function tileBounds($column, $row)
    {
        // position
        $offsetX = $column == 0 ? 0 : $this->overlap();
        $offsetY = $row == 0 ? 0 : $this->overlap();
        $x = ($column * $this->tileSize()) - $offsetX;
        $y = ($row * $this->tileSize()) - $offsetY;

        // size
        $width = $this->tileSize() + ($column == 0 ? 1 : 2) * $this->overlap();
        $height = $this->tileSize() + ($row == 0 ? 1 : 2) * $this->overlap();
        $newWidth = min($width, $this->width() - $x);
        $newHeight = min($height, $this->height() - $y);

        return array_merge(['x' => $x, 'y' => $y, 'width' => $newWidth, 'height' => $newHeight]);
    }
}
