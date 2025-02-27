<?php
/**
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://phing.info>.
 */

/**
 * Filter which includes only those lines that contain the user-specified
 * regular expression matching strings.
 *
 * Example:
 * <pre><linecontainsregexp>
 *   <regexp pattern="foo*">
 * </linecontainsregexp></pre>
 *
 * Or:
 *
 * <pre><filterreader classname="phing.filters.LineContainsRegExp">
 *    <param type="regexp" value="foo*"/>
 * </filterreader></pre>
 *
 * This will fetch all those lines that contain the pattern <code>foo</code>
 *
 * @author  Yannick Lecaillez <yl@seasonfive.com>
 * @author  Hans Lellelid <hans@xmpl.org>
 * @see     FilterReader
 * @package phing.filters
 */
class LineContainsRegexp extends BaseParamFilterReader implements ChainableReader
{

    /**
     * Parameter name for regular expression.
     *
     * @var string
     */
    const REGEXP_KEY = "regexp";
    const NEGATE_KEY = 'negate';
    const CS_KEY = 'casesensitive';

    /**
     * Regular expressions that are applied against lines.
     *
     * @var RegularExpression[]
     */
    private $_regexps = [];

    /**
     * @var bool $negate
     */
    private $negate = false;

    /**
     * @var bool $casesensitive
     */
    private $casesensitive = true;

    /**
     * Returns all lines in a buffer that contain specified strings.
     *
     * @param int $len
     * @return mixed buffer, -1 on EOF
     * @throws IOException
     * @throws RegexpException
     */
    public function read($len = null)
    {
        if (!$this->getInitialized()) {
            $this->_initialize();
            $this->setInitialized(true);
        }

        $buffer = $this->in->read($len);

        if ($buffer === -1) {
            return -1;
        }

        $lines = explode("\n", $buffer);
        $matched = [];

        $regexpsSize = count($this->_regexps);
        foreach ($lines as $line) {
            for ($i = 0; $i < $regexpsSize; $i++) {
                $regexp = $this->_regexps[$i];
                $re = $regexp->getRegexp($this->getProject());
                $re->setIgnoreCase(!$this->casesensitive);
                $matches = $re->matches($line);
                if (!$matches) {
                    $line = null;
                    break;
                }
            }
            if ($line !== null) {
                $matched[] = $line;
            }
        }
        $filtered_buffer = implode("\n", $matched);

        if ($this->isNegated()) {
            $filtered_buffer = implode("\n", array_diff($lines, $matched));
        }

        return $filtered_buffer;
    }

    /**
     * Whether to match casesensitevly.
     * @param bool $b
     */
    public function setCaseSensitive(bool $b)
    {
        $this->casesensitive = $b;
    }

    /**
     * Find out whether we match casesensitevly.
     *
     * @return boolean negation flag.
     */
    public function isCaseSensitive()
    {
        return $this->casesensitive;
    }

    /**
     * Set the negation mode.  Default false (no negation).
     *
     * @param boolean $b the boolean negation mode to set.
     */
    public function setNegate(bool $b)
    {
        $this->negate = $b;
    }

    /**
     * Find out whether we have been negated.
     *
     * @return boolean negation flag.
     */
    public function isNegated()
    {
        return $this->negate;
    }

    /**
     * Adds a <code>regexp</code> element.
     *
     * @return object regExp The <code>regexp</code> element added.
     */
    public function createRegexp()
    {
        $num = array_push($this->_regexps, new RegularExpression());

        return $this->_regexps[$num - 1];
    }

    /**
     * Sets the vector of regular expressions which must be contained within
     * a line read from the original stream in order for it to match this
     * filter.
     *
     * @param    array $regexps
     * @throws   Exception
     * @internal param An $regexps array of regular expressions which must be contained
     *                within a line in order for it to match in this filter. Must not be
     *                <code>null</code>.
     */
    public function setRegexps($regexps)
    {
        // type check, error must never occur, bad code of it does
        if (!is_array($regexps)) {
            throw new Exception("Expected an 'array', got something else");
        }
        $this->_regexps = $regexps;
    }

    /**
     * Returns the array of regular expressions which must be contained within
     * a line read from the original stream in order for it to match this
     * filter.
     *
     * @return array The array of regular expressions which must be contained within
     *               a line read from the original stream in order for it to match this
     *               filter. The returned object is "live" - in other words, changes made to
     *               the returned object are mirrored in the filter.
     */
    public function getRegexps()
    {
        return $this->_regexps;
    }

    /**
     * Set the regular expression as an attribute.
     */
    public function setRegexp($pattern)
    {
        $regexp = new RegularExpression();
        $regexp->setPattern($pattern);
        $this->_regexps[] = $regexp;
    }

    /**
     * Creates a new LineContainsRegExp using the passed in
     * Reader for instantiation.
     *
     * @param Reader $reader
     * @return LineContainsRegexp A new filter based on this configuration, but filtering
     *                the specified reader
     * @throws Exception
     * @internal param A $object Reader object providing the underlying stream.
     *               Must not be <code>null</code>.
     */
    public function chain(Reader $reader): Reader
    {
        $newFilter = new LineContainsRegexp($reader);
        $newFilter->setRegexps($this->getRegexps());
        $newFilter->setNegate($this->isNegated());
        $newFilter->setCaseSensitive($this->isCaseSensitive());
        $newFilter->setInitialized(true);
        $newFilter->setProject($this->getProject());

        return $newFilter;
    }

    /**
     * Parses parameters to add user defined regular expressions.
     */
    private function _initialize()
    {
        $params = $this->getParameters();
        if ($params !== null) {
            for ($i = 0, $paramsCount = count($params); $i < $paramsCount; $i++) {
                if (self::REGEXP_KEY === $params[$i]->getType()) {
                    $pattern = $params[$i]->getValue();
                    $regexp = new RegularExpression();
                    $regexp->setPattern($pattern);
                    $this->_regexps[] = $regexp;
                } elseif (self::NEGATE_KEY === $params[$i]->getType()) {
                    $this->setNegate(Project::toBoolean($params[$i]->getValue()));
                } elseif (self::CS_KEY === $params[$i]->getType()) {
                    $this->setCaseSensitive(Project::toBoolean($params[$i]->getValue()));
                }
            }
        }
    }
}
