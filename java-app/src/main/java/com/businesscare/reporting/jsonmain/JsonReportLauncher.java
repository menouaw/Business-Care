package com.businesscare.reporting.jsonmain;

import com.businesscare.reporting.client.ApiDataFetcher;
import com.businesscare.reporting.exception.ApiException;
import com.businesscare.reporting.service.ReportService;
import com.businesscare.reporting.util.Constants;
import com.businesscare.reporting.util.OutputDirectoryUtil;

import com.fasterxml.jackson.databind.ObjectMapper;
import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.SerializationFeature;
import com.fasterxml.jackson.datatype.jsr310.JavaTimeModule;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import java.io.File;
import java.io.FileWriter;
import java.io.IOException;
import java.time.LocalDate;
import java.time.format.DateTimeFormatter;

import com.businesscare.reporting.model.AllData;
import com.businesscare.reporting.model.ProcessedStats;

public class JsonReportLauncher {

    private static final Logger logger = LoggerFactory.getLogger(JsonReportLauncher.class);


    private static final String LOG_GEN_JSON_START = "Génération de la sortie JSON...";
    private static final String LOG_GEN_JSON_SUCCESS = "Sortie JSON générée et sauvegardée vers {}.";
    private static final String LOG_ERR_JSON_IO = "Erreur I/O lors de la génération du fichier JSON : {} ({})";

    public static void main(String[] args) {
        logger.info(Constants.LOG_APP_START);
        ReportService reportService = new ReportService();

        try {
            ApiDataFetcher dataFetcher = new ApiDataFetcher();
            AllData data = dataFetcher.fetchAllData();

            ProcessedStats stats = reportService.processData(data);

            generateJsonOutput(stats);

        } catch (ApiException e) {
            logger.error(Constants.LOG_ERR_API, e.getMessage(), e.getCause() != null ? e.getCause().getMessage() : "N/A", e);
        } catch (JsonProcessingException jpe) {
             logger.error(LOG_ERR_JSON_IO, jpe.getMessage(), jpe.getClass().getSimpleName(), jpe);
        } catch (IOException ioe) {
             logger.error(LOG_ERR_JSON_IO, "N/A", ioe.getMessage(), ioe);
        } catch (Exception e) {
            logger.error(Constants.LOG_ERR_UNEXPECTED, e.getMessage(), e.getClass().getSimpleName(), e);
        }

        logger.info(Constants.LOG_APP_END);
    }

    private static void generateJsonOutput(ProcessedStats stats) throws JsonProcessingException, IOException {
        logger.info(LOG_GEN_JSON_START);
        ObjectMapper objectMapper = new ObjectMapper();
        objectMapper.registerModule(new JavaTimeModule());
        objectMapper.enable(SerializationFeature.INDENT_OUTPUT);

        String jsonString = objectMapper.writeValueAsString(stats);

        
        LocalDate today = LocalDate.now();
        DateTimeFormatter formatter = DateTimeFormatter.ofPattern(Constants.DATE_FORMAT_PATTERN);
        String formattedDate = today.format(formatter);
        String dynamicFilename = Constants.JSON_FILENAME_PREFIX + formattedDate + Constants.JSON_FILENAME_SUFFIX;
        String outputDirectory = Constants.OUTPUT_DIRECTORY + File.separator + Constants.JSON_OUTPUT_SUBDIRECTORY;
        String outputPath = outputDirectory + File.separator + dynamicFilename;

        OutputDirectoryUtil.createOutputDirectory(outputDirectory);

        
        try (FileWriter fileWriter = new FileWriter(outputPath)) {
            fileWriter.write(jsonString);
        }

        logger.info(LOG_GEN_JSON_SUCCESS, outputPath);
        System.out.println("Sortie JSON générée et sauvegardée vers " + outputPath + ":");
        System.out.println(jsonString);
    }
} 