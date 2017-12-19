<?php
namespace App\Mail;

class Templater
{
    /**
     * @var string
     */
    private $template;
    /**
     * @var string[] varname => string value
     */
    private $vars;

    public function __construct($template, array $vars = array())
    {
        $this->template = (string)$template;
        $this->setVars($vars);
    }

    public function setVars(array $vars)
    {
        $this->vars = $vars;
    }

    public function setTemplate($template)
    {
        $this->template = (string)$template;
    }

    public function __toString()
    {
        return strtr($this->template, $this->getReplacementPairs());
    }

    private function getReplacementPairs()
    {
        $pairs = array();
        foreach ($this->vars as $name => $value)
        {
            $key = sprintf('[{%s}]', strtoupper($name));
            $pairs[$key] = is_array($value) ? implode(", ", $value) : (string)$value;
        }
        return $pairs;
    }
}