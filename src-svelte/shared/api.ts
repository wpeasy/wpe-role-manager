/**
 * REST API Client for WPE Role Manager
 *
 * @package WP_Easy\RoleManager
 */

export interface WPEData {
  apiUrl: string;
  nonce: string;
  version: string;
  pluginUrl: string;
  settings: Record<string, unknown>;
  i18n: Record<string, string>;
}

declare global {
  interface Window {
    wpeRmData: WPEData;
  }
}

export interface ApiError {
  code: string;
  message: string;
  data?: {
    status: number;
  };
}

class ApiClient {
  private baseUrl: string;
  private nonce: string;

  constructor() {
    this.baseUrl = window.wpeRmData?.apiUrl || '';
    this.nonce = window.wpeRmData?.nonce || '';
  }

  /**
   * Make an API request
   */
  private async request<T>(endpoint: string, options: RequestInit = {}): Promise<T> {
    const url = `${this.baseUrl}${endpoint}`;
    const headers: HeadersInit = {
      'Content-Type': 'application/json',
      'X-WP-Nonce': this.nonce,
      ...(options.headers || {}),
    };

    const response = await fetch(url, {
      ...options,
      headers,
    });

    const data = await response.json();

    if (!response.ok) {
      const error = data as ApiError;
      throw new Error(error.message || `API request failed: ${response.status}`);
    }

    return data as T;
  }

  /**
   * GET request
   */
  async get<T>(endpoint: string): Promise<T> {
    return this.request<T>(endpoint, { method: 'GET' });
  }

  /**
   * POST request
   */
  async post<T>(endpoint: string, data?: unknown): Promise<T> {
    return this.request<T>(endpoint, {
      method: 'POST',
      body: data ? JSON.stringify(data) : undefined,
    });
  }

  /**
   * PATCH request
   */
  async patch<T>(endpoint: string, data?: unknown): Promise<T> {
    return this.request<T>(endpoint, {
      method: 'PATCH',
      body: data ? JSON.stringify(data) : undefined,
    });
  }

  /**
   * DELETE request
   */
  async delete<T>(endpoint: string): Promise<T> {
    return this.request<T>(endpoint, { method: 'DELETE' });
  }

  /**
   * Get the base URL
   */
  getBaseUrl(): string {
    return this.baseUrl;
  }

  /**
   * Get the nonce
   */
  getNonce(): string {
    return this.nonce;
  }
}

export const api = new ApiClient();
