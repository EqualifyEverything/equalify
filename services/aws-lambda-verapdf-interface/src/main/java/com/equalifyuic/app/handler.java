package com.equalifyuic.app;

import java.io.BufferedReader;
import java.io.File;
import java.io.IOException;
import java.io.InputStreamReader;
import java.net.MalformedURLException;
import java.net.URI;
import java.net.URISyntaxException;
import java.net.URL;

import org.apache.commons.io.FileUtils;

import com.amazonaws.services.lambda.runtime.Context;
import com.amazonaws.services.lambda.runtime.LambdaLogger;
import com.amazonaws.services.lambda.runtime.RequestHandler;

public class handler implements RequestHandler<String, String> {
    @Override
    public String handleRequest(String input, Context context) {
        LambdaLogger logger = context.getLogger();
        
        logger.log("Received URL: " + input);

        // Parse URL
        URL url = null;
        try {
            url = new URI(input).toURL();
        } catch (MalformedURLException | URISyntaxException e) {
            logger.log(e.getMessage());
        }

        if (url == null) {
            throw new IllegalArgumentException("URL format error!");
        }
        String path = url.getPath();
        String FILE_NAME = new File(path).getName() != "" ? new File(path).getName() : "file.pdf";
        int CONNECT_TIMEOUT = 30 * 1000;
        int READ_TIMEOUT = 30 * 1000;
        File filePath = new File("tmp/" + FILE_NAME);

        // Save the PDF to /tmp
        try {
            FileUtils.copyURLToFile(
                    url,
                    filePath,
                    CONNECT_TIMEOUT,
                    READ_TIMEOUT);
        } catch (IOException e) {
            logger.log(e.getMessage());
        }

        // if the file doesn't end in PDF, pass the --nonpdfext flag
        String nonpdfextFlag = filePath.toString().endsWith(".pdf")
                ? ""
                : "--nonpdfext ";
        ProcessBuilder pb = new ProcessBuilder("/opt/bin/vera/verapdf", "-f", "ua2", "--format", "json", nonpdfextFlag,
                filePath.toString());

        
        String output = "";
        try {
            Process process = pb.start();
            StringBuilder outputSb = new StringBuilder();
            try (BufferedReader reader = new BufferedReader(
                    new InputStreamReader(process.getInputStream()))) {
                String line;
                // 3. Read line by line until the stream ends
                while ((line = reader.readLine()) != null) {
                    outputSb.append(line).append(System.lineSeparator());
                }
            }
            int exitCode = process.waitFor();

            if (exitCode != 0) {
                logger.log("Command exited with non-zero code: " + exitCode);
            }
            output = outputSb.toString();
            
        } catch (IOException | InterruptedException e) {
            logger.log(e.toString());
        }

        logger.log("Processing complete for " + input);
        return output;
    }
}
