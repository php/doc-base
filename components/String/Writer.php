<?php

namespace PHPDoc\Internal\String;

class Writer
{
    private string $text = '';

    public function write(string $line): self
    {
        $this->text .= $line;

        return $this;
    }

    public function writeLine(string $line): self
    {
        $this->cr();
        $this->text .= $line;

        return $this;
    }

    public function writePair($key, $value, $cr = true): self
    {
        if ($cr) {
            $this->cr();
        }
        $this->text .= $key.': '.$value;

        return $this;
    }

    /**
     * Carriage return: write a blank line
     */
    public function cr(): self
    {
        $this->text .= "\n";

        return $this;
    }

    public function text(): string
    {
        return $this->text;
    }

    public function echo(): void
    {
        $this->cr();

        echo $this->text;
    }
}
