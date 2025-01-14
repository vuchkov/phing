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
 * Sets the given property if the specified target has a timestamp
 * greater than all of the source files.
 *
 * @author  Hans Lellelid <hans@xmpl.org> (Phing)
 * @author  William Ferguson <williamf@mincom.com> (Ant)
 * @author  Hiroaki Nakamura <hnakamur@mc.neweb.ne.jp> (Ant)
 * @author  Stefan Bodewig <stefan.bodewig@epost.de> (Ant)
 * @package phing.tasks.system
 */
class UpToDateTask extends Task implements Condition
{
    use FileListAware;
    use FileSetAware;

    /**
     * @var string
     */
    private $_property;

    /**
     * @var string
     */
    private $_value;

    /**
     * @var PhingFile
     */
    private $_sourceFile;

    /**
     * @var PhingFile
     */
    private $_targetFile;

    protected $mapperElement = null;

    /**
     * The property to set if the target file is more up-to-date than
     * (each of) the source file(s).
     *
     * @param string $property the name of the property to set if Target is up-to-date.
     */
    public function setProperty($property)
    {
        $this->_property = $property;
    }

    /**
     * Get property name
     *
     * @return string property the name of the property to set if Target is up-to-date.
     */
    public function getProperty()
    {
        return $this->_property;
    }

    /**
     * The value to set the named property to if the target file is more
     * up-to-date than (each of) the source file(s). Defaults to 'true'.
     *
     * @param mixed $value the value to set the property to if Target is up-to-date
     */
    public function setValue($value)
    {
        $this->_value = $value;
    }

    /**
     * Returns the value, or "true" if a specific value wasn't provided.
     */
    private function getValue()
    {
        return $this->_value ?? "true";
    }

    /**
     * The file which must be more up-to-date than (each of) the source file(s)
     * if the property is to be set.
     *
     * @param string|PhingFile $file the file we are checking against.
     */
    public function setTargetFile($file)
    {
        if (is_string($file)) {
            $file = new PhingFile($file);
        }
        $this->_targetFile = $file;
    }

    /**
     * The file that must be older than the target file
     * if the property is to be set.
     *
     * @param string|PhingFile $file the file we are checking against the target file.
     */
    public function setSrcfile($file)
    {
        if (is_string($file)) {
            $file = new PhingFile($file);
        }
        $this->_sourceFile = $file;
    }

    /**
     * Defines the FileNameMapper to use (nested mapper element).
     */
    public function createMapper()
    {
        if ($this->mapperElement !== null) {
            throw new BuildException(
                "Cannot define more than one mapper",
                $this->getLocation()
            );
        }
        $this->mapperElement = new Mapper($this->getProject());

        return $this->mapperElement;
    }

    /**
     * Evaluate (all) target and source file(s) to
     * see if the target(s) is/are up-to-date.
     *
     * @throws BuildException
     * @return boolean
     */
    public function evaluate()
    {
        if (count($this->filesets) == 0 && count($this->filelists) == 0 && $this->_sourceFile === null) {
            throw new BuildException(
                "At least one srcfile or a nested "
                . "<fileset> or <filelist> element must be set."
            );
        }

        if ((count($this->filesets) > 0 || count($this->filelists) > 0) && $this->_sourceFile !== null) {
            throw new BuildException(
                "Cannot specify both the srcfile "
                . "attribute and a nested <fileset> "
                . "or <filelist> element."
            );
        }

        if ($this->_targetFile === null && $this->mapperElement === null) {
            throw new BuildException(
                "The targetfile attribute or a nested "
                . "mapper element must be set."
            );
        }

        // if the target file is not there, then it can't be up-to-date
        if ($this->_targetFile !== null && !$this->_targetFile->exists()) {
            return false;
        }

        // if the source file isn't there, throw an exception
        if ($this->_sourceFile !== null && !$this->_sourceFile->exists()) {
            throw new BuildException(
                $this->_sourceFile->getAbsolutePath()
                . " not found."
            );
        }

        $upToDate = true;
        for ($i = 0, $size = count($this->filesets); $i < $size && $upToDate; $i++) {
            $fs = $this->filesets[$i];
            $ds = $fs->getDirectoryScanner($this->project);
            $upToDate = $upToDate && $this->scanDir(
                $fs->getDir($this->project),
                $ds->getIncludedFiles()
            );
        }

        for ($i = 0, $size = count($this->filelists); $i < $size && $upToDate; $i++) {
            $fl = $this->filelists[$i];
            $srcFiles = $fl->getFiles($this->project);
            $upToDate = $upToDate && $this->scanDir(
                $fl->getDir($this->project),
                $srcFiles
            );
        }

        if ($this->_sourceFile !== null) {
            if ($this->mapperElement === null) {
                $upToDate = $upToDate &&
                    ($this->_targetFile->lastModified() >= $this->_sourceFile->lastModified());
            } else {
                $sfs = new SourceFileScanner($this);
                $upToDate = $upToDate &&
                    count(
                        $sfs->restrict(
                            $this->_sourceFile->getAbsolutePath(),
                            null,
                            null,
                            $this->mapperElement->getImplementation()
                        )
                    ) === 0;
            }
        }

        return $upToDate;
    }


    /**
     * Sets property to true if target file(s) have a more recent timestamp
     * than (each of) the corresponding source file(s).
     *
     * @throws BuildException
     */
    public function main()
    {
        if ($this->_property === null) {
            throw new BuildException(
                "property attribute is required.",
                $this->getLocation()
            );
        }
        $upToDate = $this->evaluate();
        if ($upToDate) {
            $property = $this->project->createTask('property');
            $property->setName($this->getProperty());
            $property->setValue($this->getValue());
            $property->setOverride(true);
            $property->main(); // execute

            if ($this->mapperElement === null) {
                $this->log(
                    "File \"" . $this->_targetFile->getAbsolutePath()
                    . "\" is up-to-date.",
                    Project::MSG_VERBOSE
                );
            } else {
                $this->log(
                    "All target files are up-to-date.",
                    Project::MSG_VERBOSE
                );
            }
        }
    }

    /**
     * @param PhingFile $srcDir
     * @param $files
     * @return bool
     */
    protected function scanDir(PhingFile $srcDir, $files)
    {
        $sfs = new SourceFileScanner($this);
        $mapper = null;
        $dir = $srcDir;
        if ($this->mapperElement === null) {
            $mm = new MergeMapper();
            $mm->setTo($this->_targetFile->getAbsolutePath());
            $mapper = $mm;
            $dir = null;
        } else {
            $mapper = $this->mapperElement->getImplementation();
        }

        return (count($sfs->restrict($files, $srcDir, $dir, $mapper)) === 0);
    }
}
