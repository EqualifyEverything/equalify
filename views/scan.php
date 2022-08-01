<section>
    <div class="mb-3 pb-3 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h1>Scan</h1>
        </div>
        <div>
            <a href="#" class="btn btn-primary">
                Start Scan
            </a>
        </div>
    </div>
    <div id="terminal" class="bg-dark text-white">
        <pre><code>
            
            <?php
            while (@ ob_end_flush()); // end all output buffers if any
            $command = $GLOBALS['PHP_PATH'].
            ' cli/scan.php';
            $process = popen($command, 'r');
            while (!feof($process))
            {
                echo fread($process, 4096);
                @ flush();
            }        
            ?>

        <pre><code>
    </div>
</section>