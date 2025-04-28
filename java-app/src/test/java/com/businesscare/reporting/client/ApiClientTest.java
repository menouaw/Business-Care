package com.businesscare.reporting.client;

import com.businesscare.reporting.model.*;
import com.businesscare.reporting.model.enums.*;
import com.businesscare.reporting.client.ApiConfig;
import com.businesscare.reporting.exception.ApiException;
import com.fasterxml.jackson.databind.ObjectMapper;
import org.apache.hc.client5.http.classic.methods.HttpGet;
import org.apache.hc.client5.http.classic.methods.HttpPost;
import org.apache.hc.client5.http.impl.classic.CloseableHttpClient;
import org.apache.hc.core5.http.ClassicHttpResponse;
import org.apache.hc.core5.http.Header;
import org.apache.hc.core5.http.HttpEntity;
import org.apache.hc.core5.http.io.HttpClientResponseHandler;
import org.apache.hc.core5.http.message.BasicHeader;
import org.apache.hc.core5.http.ProtocolException;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.junit.jupiter.api.extension.ExtendWith;
import org.mockito.ArgumentCaptor;
import org.mockito.Captor;
import org.mockito.Mock;
import org.mockito.junit.jupiter.MockitoExtension;

import java.io.ByteArrayInputStream;
import java.io.IOException;
import java.net.URISyntaxException;
import java.nio.charset.StandardCharsets;
import java.util.List;

import static org.junit.jupiter.api.Assertions.*;
import static org.mockito.ArgumentMatchers.any;
import static org.mockito.Mockito.*;

import com.businesscare.reporting.model.AuthResponse;
import com.businesscare.reporting.model.Company;
import com.businesscare.reporting.model.User;

@ExtendWith(MockitoExtension.class)
class ApiClientTest {

    @Mock
    private CloseableHttpClient mockHttpClient;
    @Mock
    private ClassicHttpResponse mockHttpResponse;
    @Mock
    private HttpEntity mockHttpEntity;

    @Captor
    private ArgumentCaptor<HttpPost> httpPostCaptor;
    @Captor
    private ArgumentCaptor<HttpGet> httpGetCaptor;

    private ApiClient apiClient;
    private final ObjectMapper objectMapper = new ObjectMapper(); 
    private final String BASE_URL = "http://test.api/";

    @BeforeEach
    void setUp() {
        
        apiClient = new ApiClient(BASE_URL, mockHttpClient, objectMapper);
    }

    
    private void mockApiResponse(int statusCode, String jsonBody) throws IOException {
        when(mockHttpResponse.getCode()).thenReturn(statusCode);
        when(mockHttpEntity.getContent()).thenReturn(new ByteArrayInputStream(jsonBody.getBytes(StandardCharsets.UTF_8)));
        
        
        
        
        lenient().when(mockHttpEntity.getContentLength()).thenReturn((long) jsonBody.getBytes(StandardCharsets.UTF_8).length);
        lenient().when(mockHttpEntity.getContentType()).thenReturn("application/json");
        when(mockHttpResponse.getEntity()).thenReturn(mockHttpEntity);

        
        
        lenient().when(mockHttpClient.execute(any(HttpPost.class), any(HttpClientResponseHandler.class)))
                 .thenAnswer(invocation -> {
                     HttpClientResponseHandler handler = invocation.getArgument(1);
                     return handler.handleResponse(mockHttpResponse);
                 });
        lenient().when(mockHttpClient.execute(any(HttpGet.class), any(HttpClientResponseHandler.class)))
                .thenAnswer(invocation -> {
                    HttpClientResponseHandler handler = invocation.getArgument(1);
                    return handler.handleResponse(mockHttpResponse);
                });
    }

    @Test
    void testLogin_Success() throws Exception {
        String responseJson = "{\"error\":false, \"message\":\"Authentification réussie\", \"token\":\"fake-jwt-token\", \"user\":{\"id\":1, \"nom\":\"Admin\", \"prenom\":\"Admin\", \"email\":\"admin@businesscare.fr\", \"role_id\":1}}";
        mockApiResponseForPost(200, responseJson);

        AuthResponse authResponse = apiClient.login("admin@businesscare.fr", "password");

        assertNotNull(authResponse);
        assertFalse(authResponse.isError());
        assertEquals("fake-jwt-token", authResponse.getToken());
        assertNotNull(authResponse.getUser());
        assertEquals(1, authResponse.getUser().getId());
        assertEquals("admin@businesscare.fr", authResponse.getUser().getEmail());

        verify(mockHttpClient).execute(httpPostCaptor.capture(), any(HttpClientResponseHandler.class));
        assertEquals(BASE_URL + "auth.php", httpPostCaptor.getValue().getUri().toString());
    }

    @Test
    void testLoginFailure_InvalidCredentials() throws Exception {
        String failureJson = "{\"error\": true, \"message\": \"Identifiants invalides ou accès non autorisé.\"}";
        mockApiResponseForPost(401, failureJson);

        ApiException exception = assertThrows(ApiException.class, () -> {
            apiClient.login("admin@test.com", "wrongpassword");
        });
        String expectedMessage = "API authentication failed: Identifiants invalides ou accès non autorisé.";
        assertEquals(expectedMessage, exception.getMessage(),
                     "Exception message should match the expected format.");
    }

    @Test
    void testGetCompanies_Success() throws Exception {
        String loginJson = "{\"error\":false, \"token\":\"test-token\", \"user\":{\"id\":1}}";
        mockApiResponseForPost(200, loginJson);
        apiClient.login("user", "pass");

        String companiesJson = "{\"error\":false, \"message\":\"Liste des entreprises\", \"data\":[{\"id\":1, \"nom\":\"Test Corp\"},{\"id\":2, \"nom\":\"Demo LLC\"}]}";
        mockApiResponseForGet(200, companiesJson);

        List<Company> companies = apiClient.getCompanies();

        assertNotNull(companies);
        assertEquals(2, companies.size());
        assertEquals(1, companies.get(0).getId());
        assertEquals("Test Corp", companies.get(0).getNom());
        assertEquals(2, companies.get(1).getId());
        assertEquals("Demo LLC", companies.get(1).getNom());

        verify(mockHttpClient).execute(httpGetCaptor.capture(), any(HttpClientResponseHandler.class));
        HttpGet capturedGet = httpGetCaptor.getValue();
        assertEquals(BASE_URL + "companies.php", capturedGet.getUri().toString());
        Header authHeader = capturedGet.getFirstHeader("Authorization");
        assertNotNull(authHeader, "Authorization header is missing");
        assertEquals("Bearer test-token", authHeader.getValue());
    }

    @Test
    void testGetCompaniesFailure_Unauthorized() throws Exception {
        String loginJson = "{\"error\":false, \"token\":\"valid-token\", \"user\":{\"id\":1}}";
        mockApiResponseForPost(200, loginJson);
        apiClient.login("user", "pass");

        String unauthorizedJson = "{\"error\": true, \"message\": \"Authentification requise\"}";
        mockApiResponseForGet(401, unauthorizedJson);

        ApiException exception = assertThrows(ApiException.class, () -> {
            apiClient.getCompanies();
        });

        assertTrue(exception.getMessage().contains("Échec de la récupération des entreprises: Authentification requise"),
                   "Exception message should indicate failure cause. Was: " + exception.getMessage());
    }

    @Test
    void testGetCompaniesFailure_NotLoggedIn() throws Exception {
        apiClient = new ApiClient(BASE_URL, mockHttpClient, objectMapper);

        ApiException exception = assertThrows(ApiException.class, () -> {
            apiClient.getCompanies();
        });

        assertTrue(exception.getMessage().contains("Non authentifié. Appeler login() d'abord."),
                   "Exception message should indicate not authenticated. Was: " + exception.getMessage());

        verify(mockHttpClient, never()).execute(any(HttpGet.class), any(HttpClientResponseHandler.class));
    }

    private void mockApiResponseForPost(int statusCode, String jsonBody) throws IOException {
        when(mockHttpResponse.getCode()).thenReturn(statusCode);
        when(mockHttpEntity.getContent()).thenReturn(new ByteArrayInputStream(jsonBody.getBytes(StandardCharsets.UTF_8)));
        Header contentTypeHeader = new BasicHeader("Content-Type", "application/json; charset=utf-8");
        lenient().when(mockHttpEntity.getContentType()).thenReturn("application/json; charset=utf-8");
        try {
            lenient().when(mockHttpResponse.getHeader("Content-Type")).thenReturn(contentTypeHeader);
            lenient().when(mockHttpResponse.getHeaders("Content-Type")).thenReturn(new Header[]{contentTypeHeader});
        } catch (ProtocolException e) {
            throw new RuntimeException("Unexpected ProtocolException in mock setup", e);
        }

        when(mockHttpResponse.getEntity()).thenReturn(mockHttpEntity);

        when(mockHttpClient.execute(any(HttpPost.class), any(HttpClientResponseHandler.class)))
                .thenAnswer(invocation -> {
                    HttpClientResponseHandler handler = invocation.getArgument(1);
                    return handler.handleResponse(mockHttpResponse);
                });
    }

    private void mockApiResponseForGet(int statusCode, String jsonBody) throws IOException {
        when(mockHttpResponse.getCode()).thenReturn(statusCode);
        when(mockHttpEntity.getContent()).thenReturn(new ByteArrayInputStream(jsonBody.getBytes(StandardCharsets.UTF_8)));
        Header contentTypeHeader = new BasicHeader("Content-Type", "application/json; charset=utf-8");
        lenient().when(mockHttpEntity.getContentType()).thenReturn("application/json; charset=utf-8");
        try {
            lenient().when(mockHttpResponse.getHeader("Content-Type")).thenReturn(contentTypeHeader);
            lenient().when(mockHttpResponse.getHeaders("Content-Type")).thenReturn(new Header[]{contentTypeHeader});
        } catch (ProtocolException e) {
            throw new RuntimeException("Unexpected ProtocolException in mock setup", e);
        }

        when(mockHttpResponse.getEntity()).thenReturn(mockHttpEntity);

        when(mockHttpClient.execute(any(HttpGet.class), any(HttpClientResponseHandler.class)))
                .thenAnswer(invocation -> {
                    HttpClientResponseHandler handler = invocation.getArgument(1);
                    return handler.handleResponse(mockHttpResponse);
                });
    }
} 