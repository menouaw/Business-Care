package com.businesscare.reporting.util;

import com.businesscare.reporting.exception.ReportGenerationException;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import java.io.File;
import java.io.IOException;

public class OutputDirectoryUtil {

    private static final Logger logger = LoggerFactory.getLogger(OutputDirectoryUtil.class);
    private static final String LOG_CREATE_DIR_SUCCESS = "Répertoire de sortie créé : {}";
    private static final String LOG_ERR_CREATE_DIR = "Impossible de créer le répertoire de sortie : {}";
    private static final String ERR_CREATE_DIR = "Impossible de créer le répertoire de sortie: ";

    private OutputDirectoryUtil() {
        
    }

    public static void createOutputDirectory(String directoryPath) throws ReportGenerationException {
        File outputDir = new File(directoryPath);
        if (!outputDir.exists()) {
            if (outputDir.mkdirs()) {
                logger.info(LOG_CREATE_DIR_SUCCESS, directoryPath);
            } else {
                logger.error(LOG_ERR_CREATE_DIR, directoryPath);
                throw new ReportGenerationException(ERR_CREATE_DIR + directoryPath);
            }
        }
    }
} 