package com.businesscare.reporting.model;

public class ApiResponse<T> extends ErrorResponse {
    private T data;
    
    public T getData() { return data; }
    public void setData(T data) { this.data = data; }
} 