<?php

class FileNotReadableException extends Exception {}
class UnableToOpenFileException extends Exception {}
class UnableToCloseFileException extends Exception {}

class File
{

    private $fp; // File pointer to open file
    private $defaultFileMode = 'w+';

    private $buff_fileContent = array();

    private $strFileContent = '';

    private static $lastFilePath;

    public function __construct( $filePath = null, $fileMode = null)
    {
        $this->open( $filePath, $fileMode );
    }

    public function open( $filePath, $fileMode = null )
    {
        ( $filePath == null && static::$lastFilePath !== null ) && $filePath = static::$lastFilePath;

        // set the file mode
        $fileMode == null && $fileMode = $this->defaultFileMode;

        // Chech if file is readable, and file can be opened automatically in file mode
        if( ! is_readable( $filePath ) && strpos($fileMode, 'r') !== FALSE )
        {
            throw new FileNotReadableException(_("Given file is not readable!"));
        }

        $this->fp = @fopen( $filePath, $fileMode ); // error messages supressed for a reason

        if( ! $this->fp )
        {
            throw new UnableToOpenFileException(_("Could not open file for reading"));
        }

        // Set the current filepath for later use
        static::$lastFilePath = $filePath;
    }

    // Close file
    public function close( $flush = false )
    {
        if( $flush AND ( ! empty($this->strFileContent) || 0 < sizeof($this->buff_fileContent)) )
        {
            $this->write();
        }

        fclose($this->fp);
    }

    // Writes content to a specific line of the file
    public function setLine( $line, $lineContent )
    {
        if( sizeof($this->buff_fileContent) == 0 )
        {
            $this->getContent();
        }

        $this->buff_fileContent[$line] = $lineContent;

        $max = max(array_flip($this->buff_fileContent));
        $nLine = $max > $line ? $max : $line;

        for($i=0;$i<$nLine;$i++)
        {
            ( ! isset($this->buff_fileContent[$i])) && $this->buff_fileContent[$i] = '';
        }

        // Sort for correct order
        ksort($this->buff_fileContent);
    }

    public function flushBuffer()
    {
        ( sizeof($this->buff_fileContent) > 0 ) && $this->strFileContent = implode($this->buff_fileContent, "\n");
    }

    public function setContent( $content )
    {
        // clear buffer content, we make our own output
        $this->buff_fileContent = array();

        // Set file content
        $this->strFileContent = $content;
    }

    // Resets file pointer to the beginning of the file
    public function write()
    {
        $this->flushBuffer();

        fseek($this->fp, 0);
        fwrite($this->fp, $this->strFileContent);
    }

    // Returns specified line from file
    public function getLine( $line )
    {
        if( sizeof($this->buff_fileContent) == 0 )
        {
            $this->getContent();
        }

        if( isset( $this->buff_fileContent[$line]))
        {
            return $this->buff_fileContent[$line];
        }

        return FALSE;
    }

    public function getContent()
    {
        $this->buff_fileContent = array(); // empty the content buffer
        $ptr_lastLine = $ptr_line = 0; // set the start position
        $buff_line = ''; // init an intermediate buffer for lines

        // reset the file pointer position, to the beginning of the file
        fseek($this->fp, $ptr_line);

        while( FALSE !== ($char = fgetc($this->fp)))
        {
            if( $char === "\n" || is_array($char) )
            {
                // add line to buffer
                $this->buff_fileContent[$ptr_lastLine] = $buff_line;
                $buff_line = ''; // clear the intermediate buffer
                $ptr_lastLine = ++$ptr_line; // set the next line position
            }
            else
            {
                // add character to the intermediate buffer
                $buff_line .= $char;
            }

        }

        // add the last line to the buffer
        $this->buff_fileContent[$ptr_lastLine] = $buff_line;

        // Flush buffer to get an output
        $this->flushBuffer();

        return $this->strFileContent;
    }

    // Lock file 
    // default exclusively
    public function lock( $lockType = LOCK_EX )
    {
        flock( $this->fp, $lockType );
    }

    // unlock file
    public function unlock()
    {
        flock( $this->fp, LOCK_UN );
    }
}
