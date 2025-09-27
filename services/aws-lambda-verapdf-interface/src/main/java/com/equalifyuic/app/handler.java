package com.equalifyuic.app;

import java.io.ByteArrayOutputStream;
//import java.io.BufferedReader;
import java.io.File;
//import java.io.FileInputStream;
//import java.io.FileOutputStream;
import java.io.IOException;
import java.io.OutputStream;
//import java.io.InputStream;
//import java.io.InputStreamReader;
import java.net.MalformedURLException;
import java.net.URI;
import java.net.URISyntaxException;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.util.ArrayList;
import java.util.EnumSet;
import java.util.List;
//import java.util.Map;

import org.apache.commons.io.FileUtils;

import com.amazonaws.services.lambda.runtime.Context;
import com.amazonaws.services.lambda.runtime.LambdaLogger;
import com.amazonaws.services.lambda.runtime.RequestHandler;
//import com.fasterxml.jackson.databind.ObjectMapper;

//import org.verapdf.core.EncryptedPdfException;
//import org.verapdf.core.ModelParsingException;
//import org.verapdf.core.ValidationException;
import org.verapdf.core.VeraPDFException;
import org.verapdf.features.FeatureExtractorConfig;
import org.verapdf.features.FeatureFactory;
//import org.verapdf.core.VeraPDFException;
import org.verapdf.gf.foundry.VeraGreenfieldFoundryProvider;
import org.verapdf.metadata.fixer.FixerFactory;
import org.verapdf.metadata.fixer.MetadataFixerConfig;
//import org.verapdf.pdfa.Foundries;
//import org.verapdf.pdfa.PDFAParser;
//import org.verapdf.pdfa.results.ValidationResult;
//import org.verapdf.pdfa.validation.profiles.RuleId;
import org.verapdf.pdfa.validation.validators.ValidatorConfig;
import org.verapdf.pdfa.validation.validators.ValidatorConfigBuilder;
//import org.verapdf.pdfa.validation.validators.ValidatorFactory;
import org.verapdf.processor.BatchProcessor;
import org.verapdf.processor.FormatOption;
import org.verapdf.processor.ProcessorConfig;
import org.verapdf.processor.ProcessorFactory;
import org.verapdf.processor.TaskType;
import org.verapdf.processor.plugins.PluginsCollectionConfig;
//import org.verapdf.processor.reports.ValidationReport;
//import org.verapdf.pdfa.validation.validators.ValidatorFactory;
//import org.verapdf.processor.reports.multithread.writer.JsonReportWriter;
//import org.verapdf.pdfa.PDFAValidator;
//import org.verapdf.pdfa.flavours.PDFAFlavour;
import org.verapdf.pdfa.flavours.PDFAFlavour;

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
        File filePath = new File("/tmp/" + FILE_NAME);

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

        VeraGreenfieldFoundryProvider.initialise();
        String output = "";
        PDFAFlavour flavor = PDFAFlavour.PDFUA_2;
        //ValidatorConfig validatorConfig = ValidatorFactory.defaultConfig();
        // or it is possible to specify required parameters using
        // ValidatorConfigBuilder. For example, to specify a validation flavour
        // explicitly:
        ValidatorConfig validatorConfig = new ValidatorConfigBuilder().flavour(flavor).build();
        // Default features config
        FeatureExtractorConfig featureConfig = FeatureFactory.defaultConfig();
        // Default plugins config
        PluginsCollectionConfig pluginsConfig = PluginsCollectionConfig.defaultConfig();
        // Default fixer config
        MetadataFixerConfig fixerConfig = FixerFactory.defaultConfig();
        // Tasks configuring
        EnumSet<TaskType> tasks = EnumSet.noneOf(TaskType.class);
        tasks.add(TaskType.VALIDATE);
        tasks.add(TaskType.EXTRACT_FEATURES);
        tasks.add(TaskType.FIX_METADATA);
        // Creating processor config
        ProcessorConfig processorConfig = ProcessorFactory.fromValues(validatorConfig, featureConfig, pluginsConfig,
                fixerConfig, tasks);
        // Creating processor and output stream. In this example output stream is
        // System.out
        ByteArrayOutputStream baos = new ByteArrayOutputStream();
        try (
                BatchProcessor processor = ProcessorFactory.fileBatchProcessor(processorConfig);
                OutputStream reportStream = baos) {
            // Generating list of files for processing
            List<File> files = new ArrayList<>();
            files.add(filePath);
            // starting the processor
            processor.process(files, ProcessorFactory.getHandler(FormatOption.JSON, true, reportStream,
                    processorConfig.getValidatorConfig().isRecordPasses()));
            output = baos.toString(StandardCharsets.UTF_8.name());
        } catch (VeraPDFException e) {
            System.err.println("Exception raised while processing batch");
            e.printStackTrace();
        } catch (IOException excep) {
            System.err.println("Exception raised closing MRR temp file.");
            excep.printStackTrace();
        }
        /*
         * try (PDFAParser parser = Foundries.defaultInstance().createParser(new
         * FileInputStream(filePath), flavour)) {
         * PDFAValidator validator =
         * Foundries.defaultInstance().createValidator(flavour, 10000,
         * false, true, false);
         * ValidationResult result = validator.validate(parser);
         * new ValidatorConfigBuilder().flavour(PDFAFlavour.PDFA_4).build();
         * 
         * // Use Jackson ObjectMapper to write the ValidationResult object as JSON
         * ObjectMapper mapper = new ObjectMapper();
         * 
         * output = mapper.writerWithDefaultPrettyPrinter().writeValueAsString(result.
         * getFailedChecks());
         * 
         * /*
         * if (result.isCompliant()) {
         * // File is a valid PDF/A 1b
         * } else {
         * // it isn't
         * }
         */
        /*
         * } catch (IOException | ValidationException | ModelParsingException |
         * EncryptedPdfException exception) {
         * // Exception during validation
         * logger.log(exception.toString());
         * }
         */
        // if the file doesn't end in PDF, pass the --nonpdfext flag
        /*
         * String nonpdfextFlag = filePath.toString().endsWith(".pdf")
         * ? ""
         * : "--nonpdfext ";
         * ProcessBuilder pb = new ProcessBuilder(
         * "/opt/java/lib/vera/verapdf",
         * "-f",
         * "ua2",
         * "--format",
         * "json",
         * nonpdfextFlag,
         * filePath.toString());
         * pb.redirectErrorStream(true); // also get errors
         * 
         * String output = "";
         * try {
         * Process process = pb.start();
         * StringBuilder outputSb = new StringBuilder();
         * try (BufferedReader reader = new BufferedReader(
         * new InputStreamReader(process.getInputStream()))) {
         * String line;
         * // 3. Read line by line until the stream ends
         * while ((line = reader.readLine()) != null) {
         * outputSb.append(line).append(System.lineSeparator());
         * }
         * }
         * int exitCode = process.waitFor();
         * 
         * if (exitCode != 0) {
         * logger.log("Command exited with non-zero code: " + exitCode);
         * }
         * output = outputSb.toString();
         * 
         * } catch (IOException | InterruptedException e) {
         * logger.log(e.toString());
         * }
         */

        logger.log("Processing complete for " + input);
        logger.log(output);
        return output;
    }
}
