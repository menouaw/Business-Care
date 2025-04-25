package com.businesscare.reporting.client;

import com.businesscare.reporting.exception.ApiException;
import com.fasterxml.jackson.databind.ObjectMapper;
import org.apache.hc.client5.http.classic.methods.HttpGet;
import org.apache.hc.client5.http.classic.methods.HttpPost;
import org.apache.hc.client5.http.impl.classic.CloseableHttpClient;
import org.apache.hc.core5.http.ClassicHttpResponse;
import org.apache.hc.core5.http.Header;
import org.apache.hc.core5.http.HttpEntity;
import org.apache.hc.core5.http.io.HttpClientResponseHandler;
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
    void testLoginSuccess() throws IOException, ApiException, URISyntaxException {
        
        String fakeToken = "test-token-123";
        String successJson = String.format(
            "{\"error\": false, \"message\": \"Authentification réussie\", \"token\": \"%s\", \"user\": {\"id\": 1, \"nom\": \"Admin\", \"prenom\": \"Test\", \"email\": \"admin@test.com\", \"role_id\": 1}}",
            fakeToken
        );
        mockApiResponse(200, successJson);

        
        ApiClient.AuthResponse response = apiClient.login("admin@test.com", "password123");

        
        assertNotNull(response);
        assertFalse(response.isError());
        assertEquals(fakeToken, response.getToken());
        assertNotNull(response.getUser());
        assertEquals(1, response.getUser().id);
        assertEquals("admin@test.com", response.getUser().email);

        
        verify(mockHttpClient).execute(httpPostCaptor.capture(), any(HttpClientResponseHandler.class));
        assertEquals(BASE_URL + "auth", httpPostCaptor.getValue().getUri().toString());
    }

    @Test
    void testLoginFailure_InvalidCredentials() throws IOException {
        
        String failureJson = "{\"error\": true, \"message\": \"Identifiants invalides ou accès non autorisé.\"}";
        mockApiResponse(401, failureJson);

        
        ApiException exception = assertThrows(ApiException.class, () -> {
            apiClient.login("admin@test.com", "wrongpassword");
        });

        assertTrue(exception.getMessage().contains("Identifiants invalides"));
    }

    @Test
    void testGetCompaniesSuccess() throws IOException, ApiException, URISyntaxException {
        
        String fakeToken = "token-for-companies";
        String loginSuccessJson = String.format(
            "{\"error\": false, \"message\": \"Authentification réussie\", \"token\": \"%s\", \"user\": {\"id\": 1, \"nom\": \"Admin\", \"prenom\": \"Test\", \"email\": \"admin@test.com\", \"role_id\": 1}}",
            fakeToken
        );
        mockApiResponse(200, loginSuccessJson);
        apiClient.login("admin@test.com", "password"); 

        
        String companiesJson = "{\"error\": false, \"data\": [{\"id\": 10, \"nom\": \"CompA\", \"siret\": \"111\"}, {\"id\": 20, \"nom\": \"CompB\", \"siret\": \"222\"}]}";
        
        mockApiResponse(200, companiesJson);

        
        List<ApiClient.Company> companies = apiClient.getCompanies();

        
        assertNotNull(companies);
        assertEquals(2, companies.size());
        assertEquals(10, companies.get(0).id);
        assertEquals("CompA", companies.get(0).nom);
        assertEquals(20, companies.get(1).id);
        assertEquals("CompB", companies.get(1).nom);

        
        verify(mockHttpClient).execute(httpGetCaptor.capture(), any(HttpClientResponseHandler.class));
        HttpGet capturedGet = httpGetCaptor.getValue();
        assertEquals(BASE_URL + "companies", capturedGet.getUri().toString());
        Header authHeader = capturedGet.getFirstHeader("Authorization");
        assertNotNull(authHeader, "Authorization header is missing");
        assertEquals("Bearer " + fakeToken, authHeader.getValue());
    }

     @Test
    void testGetCompaniesFailure_Unauthorized() throws IOException, ApiException {
        
         String fakeToken = "token-unauth";
         String loginSuccessJson = String.format(
             "{\"error\": false, \"message\": \"Authentification réussie\", \"token\": \"%s\", \"user\": {\"id\": 1, \"nom\": \"Admin\", \"prenom\": \"Test\", \"email\": \"admin@test.com\", \"role_id\": 1}}",
             fakeToken
         );
         mockApiResponse(200, loginSuccessJson);
         apiClient.login("admin@test.com", "password");

        
        String unauthorizedJson = "{\"error\": true, \"message\": \"Authentification requise\"}";
        mockApiResponse(401, unauthorizedJson);

        
        ApiException exception = assertThrows(ApiException.class, () -> {
            apiClient.getCompanies();
        });

        assertTrue(exception.getMessage().contains("Authentification requise"));
    }

     @Test
     void testGetCompaniesFailure_NotLoggedIn() throws IOException {
         

         
         ApiException exception = assertThrows(ApiException.class, () -> {
             apiClient.getCompanies();
         });

         assertTrue(exception.getMessage().contains("Not authenticated"));

         
         verify(mockHttpClient, never()).execute(any(HttpGet.class), any(HttpClientResponseHandler.class));
     }

} 