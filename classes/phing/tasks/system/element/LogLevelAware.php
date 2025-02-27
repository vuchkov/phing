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
 * @author   Siad Ardroumli <siad.ardroumli@gmail.com>
 * @package  phing.tasks.system
 */
trait LogLevelAware
{
    protected $logLevel = Project::MSG_VERBOSE;

    /**
     * Set level of log messages generated (default = verbose)
     *
     * @param string $level
     */
    public function setLevel($level): void
    {
        switch ($level) {
            case 'error':
                $this->logLevel = Project::MSG_ERR;
                break;
            case 'warning':
                $this->logLevel = Project::MSG_WARN;
                break;
            case 'info':
                $this->logLevel = Project::MSG_INFO;
                break;
            case 'verbose':
                $this->logLevel = Project::MSG_VERBOSE;
                break;
            case 'debug':
                $this->logLevel = Project::MSG_DEBUG;
                break;
            default:
                throw new BuildException(
                    sprintf('Unknown log level "%s"', $level)
                );
        }
    }
}
