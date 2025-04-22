package com.businesscare.reporting.exception;

public class ApiException extends RuntimeException {
    public ApiException(String message) {
        super(message);
    }
}
