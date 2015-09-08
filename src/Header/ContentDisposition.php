<?php

namespace Maks3w\EmailAttachments\Header;

use Zend\Mail\Header\Exception\InvalidArgumentException;
use Zend\Mail\Header\GenericHeader;
use Zend\Mail\Header\HeaderInterface;
use Zend\Mail\Header\HeaderValue;
use Zend\Mail\Header\HeaderWrap;
use Zend\Mail\Headers;

class ContentDisposition implements HeaderInterface
{
    /**
     * @var string
     */
    protected $disposition;

    /**
     * @var array
     */
    protected $parameters = [];

    public static function fromString($headerLine)
    {
        list($name, $value) = GenericHeader::splitHeaderLine($headerLine);
        $value = HeaderWrap::mimeDecodeValue($value);

        // check to ensure proper header disposition for this factory
        if (strtolower($name) !== 'content-disposition') {
            throw new InvalidArgumentException('Invalid header line for Content-Disposition string');
        }

        $value = str_replace(Headers::FOLDING, ' ', $value);
        $values = preg_split('#\s*;\s*#', $value);

        $disposition = array_shift($values);
        $header = new static();
        $header->setDisposition($disposition);

        // Remove empty values
        $values = array_filter($values);

        foreach ($values as $keyValuePair) {
            list($key, $value) = explode('=', $keyValuePair, 2);
            $value = trim($value, "'\" \t\n\r\0\x0B");
            $header->addParameter($key, $value);
        }

        return $header;
    }

    public function getFieldName()
    {
        return 'Content-Disposition';
    }

    public function getFieldValue($format = HeaderInterface::FORMAT_RAW)
    {
        $prepared = $this->disposition;
        if (empty($this->parameters)) {
            return $prepared;
        }

        $values = array($prepared);
        foreach ($this->parameters as $attribute => $value) {
            $values[] = sprintf('%s="%s"', $attribute, $value);
        }

        return implode(';' . Headers::FOLDING, $values);
    }

    public function setEncoding($encoding)
    {
        // This header must be always in US-ASCII
        return $this;
    }

    public function getEncoding()
    {
        return 'ASCII';
    }

    public function toString()
    {
        return 'Content-Disposition: ' . $this->getFieldValue();
    }

    /**
     * Set the content disposition
     *
     * @param  string $disposition
     *
     * @throws InvalidArgumentException
     * @return ContentDisposition
     */
    public function setDisposition($disposition)
    {
        switch ($disposition) {
            case 'inline':
            case 'attachment':
                break;
            default:
                throw new InvalidArgumentException(
                    sprintf(
                        '%s expects to be "inline" or "attachment". Received "%s"',
                        __METHOD__,
                        (string)$disposition
                    )
                );
                break;
        }

        $this->disposition = $disposition;
        return $this;
    }

    /**
     * Retrieve the content disposition
     *
     * @return string
     */
    public function getDisposition()
    {
        return $this->disposition;
    }

    /**
     * Add a parameter pair
     *
     * @param  string $name
     * @param  string $value
     *
     * @return ContentDisposition
     * @throws InvalidArgumentException for parameter names that do not follow RFC 2822
     * @throws InvalidArgumentException for parameter values that do not follow RFC 2822
     */
    public function addParameter($name, $value)
    {
        $name = strtolower($name);
        $value = (string)$value;

        if (!HeaderValue::isValid($name)) {
            throw new InvalidArgumentException('Invalid content-disposition parameter name detected');
        }
        if (!HeaderValue::isValid($value)) {
            throw new InvalidArgumentException('Invalid content-disposition parameter value detected');
        }

        $this->parameters[$name] = $value;
        return $this;
    }

    /**
     * Get all parameters
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Get a parameter by name
     *
     * @param  string $name
     *
     * @return null|string
     */
    public function getParameter($name)
    {
        $name = strtolower($name);
        if (isset($this->parameters[$name])) {
            return $this->parameters[$name];
        }
        return null;
    }

    /**
     * Remove a named parameter
     *
     * @param  string $name
     *
     * @return bool
     */
    public function removeParameter($name)
    {
        $name = strtolower($name);
        if (isset($this->parameters[$name])) {
            unset($this->parameters[$name]);
            return true;
        }
        return false;
    }
}
