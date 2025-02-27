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
 * FileList represents an explicitly named list of files. FileLists
 * are useful when you want to capture a list of files regardless of
 * whether they currently exist.
 *
 * <filelist
 *    id="docfiles"
 *   dir="${phing.docs.dir}"
 *   files="chapters/Installation.html,chapters/Setup.html"/>
 *
 * OR
 *
 * <filelist
 *         dir="${doc.src.dir}"
 *         listfile="${phing.docs.dir}/PhingGuide.book"/>
 *
 * (or a mixture of files="" and listfile="" can be used)
 *
 * @author  Hans Lellelid <hans@xmpl.org>
 * @package phing.types
 */
class FileList extends DataType implements IteratorAggregate
{

    // public for "cloning" purposes

    /**
     * Array containing all filenames.
     */
    public $filenames = [];

    /**
     * Base directory for this file list.
     */
    public $dir;

    /**
     * @var PhingFile that contains a list of files (one per line).
     */
    public $listfile;

    /**
     * Construct a new FileList.
     *
     * @param FileList $filelist
     */
    public function __construct($filelist = null)
    {
        parent::__construct();

        if ($filelist !== null) {
            $this->dir = $filelist->dir;
            $this->filenames = $filelist->filenames;
            $this->listfile = $filelist->listfile;
        }
    }

    public function getIterator()
    {
        if ($this->isReference()) {
            return $this->getRef($this->getProject())->getIterator();
        }

        return new ArrayIterator($this->filenames);
    }

    /**
     * Makes this instance in effect a reference to another FileList
     * instance.
     *
     * @param  Reference $r
     * @throws BuildException
     */
    public function setRefid(Reference $r)
    {
        if ($this->dir !== null || count($this->filenames) !== 0) {
            throw $this->tooManyAttributes();
        }
        parent::setRefid($r);
    }

    /**
     * Base directory for files in list.
     *
     * @param PhingFile $dir
     * @throws IOException
     * @throws NullPointerException
     */
    public function setDir(PhingFile $dir)
    {
        if ($this->isReference()) {
            throw $this->tooManyAttributes();
        }
        if (!($dir instanceof PhingFile)) {
            $dir = new PhingFile($dir);
        }
        $this->dir = $dir;
    }

    /**
     * Get the basedir for files in list.
     *
     * @param  Project $p
     * @throws BuildException
     * @return PhingFile
     */
    public function getDir(Project $p)
    {
        if ($this->isReference()) {
            $ref = $this->getRef($p);

            return $ref->getDir($p);
        }

        return $this->dir;
    }

    /**
     * Set the array of files in list.
     *
     * @param  array $filenames
     * @throws BuildException
     */
    public function setFiles($filenames)
    {
        if ($this->isReference()) {
            throw $this->tooManyAttributes();
        }
        if (!empty($filenames)) {
            $tok = strtok($filenames, ", \t\n\r");
            while ($tok !== false) {
                $fname = trim($tok);
                if ($fname !== "") {
                    $this->filenames[] = $tok;
                }
                $tok = strtok(", \t\n\r");
            }
        }
    }

    /**
     * Sets a source "list" file that contains filenames to add -- one per line.
     *
     * @param string $file
     * @throws IOException
     * @throws NullPointerException
     */
    public function setListFile($file)
    {
        if ($this->isReference()) {
            throw $this->tooManyAttributes();
        }
        if (!($file instanceof PhingFile)) {
            $file = new PhingFile($file);
        }
        $this->listfile = $file;
    }

    /**
     * Get the source "list" file that contains file names.
     *
     * @param  Project $p
     * @return PhingFile
     */
    public function getListFile(Project $p)
    {
        if ($this->isReference()) {
            $ref = $this->getRef($p);

            return $ref->getListFile($p);
        }

        return $this->listfile;
    }

    /**
     * Returns the list of files represented by this FileList.
     *
     * @param Project $p
     * @return array
     * @throws IOException
     * @throws BuildException
     */
    public function getFiles(Project $p)
    {
        if ($this->isReference()) {
            $ret = $this->getRef($p);
            $ret = $ret->getFiles($p);

            return $ret;
        }

        if ($this->dir === null) {
            throw new BuildException("No directory specified for filelist.");
        }

        if ($this->listfile !== null) {
            $this->readListFile($p);
        }

        if (empty($this->filenames)) {
            throw new BuildException("No files specified for filelist.");
        }

        return $this->filenames;
    }

    /**
     * Performs the check for circular references and returns the
     * referenced FileSet.
     *
     * @param Project $p
     *
     * @throws BuildException
     *
     * @return FileList
     */
    public function getRef(Project $p)
    {
        $dataTypeName = StringHelper::substring(__CLASS__, strrpos(__CLASS__, '\\') + 1);
        return $this->getCheckedRef(__CLASS__, $dataTypeName);
    }

    /**
     * Reads file names from a file and adds them to the files array.
     *
     * @param Project $p
     *
     * @throws BuildException
     * @throws IOException
     */
    private function readListFile(Project $p)
    {
        $listReader = null;
        try {
            // Get a FileReader
            $listReader = new BufferedReader(new FileReader($this->listfile));

            $line = $listReader->readLine();
            while ($line !== null) {
                if (!empty($line)) {
                    $line = $p->replaceProperties($line);
                    $this->filenames[] = trim($line);
                }
                $line = $listReader->readLine();
            }
        } catch (Exception $e) {
            if ($listReader) {
                $listReader->close();
            }
            throw new BuildException(
                "An error occurred while reading from list file " . $this->listfile->__toString() . ": " . $e->getMessage()
            );
        }

        $listReader->close();
    }
}
