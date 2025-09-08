// Centralized API configuration
export const API_BASE_URL = (import.meta as any)?.env?.VITE_API_BASE_URL || 'http://localhost:8000';

export const API_AUTH_URL = (import.meta as any)?.env?.VITE_API_AUTH_URL || 'http://localhost:8000';

export const API_VTU_URL = (import.meta as any)?.env?.VITE_API_VTU_URL || 'http://localhost:8000/vtu';

export const API_SMS_URL = (import.meta as any)?.env?.VITE_API_SMS_URL || 'http://localhost:8000';

export const API_PROXY_URL = (import.meta as any)?.env?.VITE_API_PROXY_URL || 'http://localhost:8000/proxy';

export const API_WALLET_URL = (import.meta as any)?.env?.VITE_API_WALLET_URL || 'http://localhost:8000/wallet';

export type ApiStatus = 'success' | 'error';

export interface ApiResponse<T> { status: ApiStatus; data?: T; message?: string }

// Auth
export interface RegisterBody { name: string; email: string; password: string }
export interface LoginBody { email: string; password: string }
export interface AuthUser { id: number; name: string; email: string; balance?: number; role?: string }

export interface AuthResponse { user: AuthUser; token: string }

// Profile
export interface ProfileData { id: number; name: string; email: string; balance: number; role: string }

// Transactions
export type TransactionStatus = 'completed' | 'pending' | 'failed';
export type TransactionType = 'credit' | 'debit';
export interface TransactionItem {
  id: number;
  type: TransactionType;
  amount: number;
  description: string;
  status: TransactionStatus;
  reference?: string;
  created_at: string;
}

// Wallet (PayVibe)
export interface InitiateTopUpBody { amount: number; user_id: number }
export interface InitiateTopUpData {
  reference: string;
  account_number: string;
  bank_name: string; // Wema Bank
  account_name: string; // Finspa/PAYVIBE
  amount: number;
  charge: number;
  final_amount: number;
  expiry: number; // seconds
  transaction_id: number | string;
}
export interface VerifyPaymentBody { reference: string }
export interface VerifyPaymentData { status: 'pending' | 'completed' | 'failed'; amount?: number }

// SMS Services
export interface SmsServiceItem { id: number; name: string; country: string; price: number; currency: string }

export interface OrderSmsNumberBody { user_id: number; service: string; country: string }

export interface OrderSmsNumberData { order_id: string | number; phone: string; cost: number; api_service_id: number | string }

export interface GetSmsCodeBody { activation_id: string; user_id: number }

export interface GetSmsCodeData { code?: string; status: 'pending' | 'received' | 'expired' }

// VTU
export type VtuType = 'airtime' | 'data';
export interface VtuServiceItem { id: number; name: string; type: VtuType; provider: string; price: number }

export interface PurchaseVtuBody { service_id: number; phone: string; amount?: number; bundle_code?: string }

export interface PurchaseVtuData { order_id: string | number; status: string } 

// Proxy
export interface ProxyServiceItem { id: number; name: string; price: number; provider: string }

export interface PurchaseProxyBody { service_id: number; region: string }

export interface PurchaseProxyData { order_id: string | number; status: string }

class ApiService {
  private baseUrl: string;

  constructor(baseUrl: string = 'http://localhost:8000') {
    this.baseUrl = baseUrl;
  }

  private async request<T>(endpoint: string, options: RequestInit = {}): Promise<ApiResponse<T>> {
    const url = `${this.baseUrl}${endpoint}`;

    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...(options.headers as Record<string, string> | undefined),
    };

    const token = localStorage.getItem('auth_token');
    if (token) headers['Authorization'] = `Bearer ${token}`;

    const init: RequestInit = { ...options, headers };

    const resp = await fetch(url, init);
    if (!resp.ok) {
      // Log non-2xx as errors (but still attempt to return JSON body)
      console.error(`HTTP ${resp.status} for ${endpoint}`);
    }
    // Throw on network-level errors only (fetch would have thrown). Here we parse JSON regardless

    const json = (await resp.json()) as ApiResponse<T>;
    return json;
  }

  // Auth
  public async register(body: RegisterBody, init?: RequestInit) {
    return this.request<ApiResponse<AuthResponse>['data']>('/register', {
      method: 'POST',
      body: JSON.stringify(body),
      ...init,
    });
  }

  public async login(body: LoginBody, init?: RequestInit) {
    return this.request<AuthResponse>('/login', {
      method: 'POST',
      body: JSON.stringify(body),
      ...init,
    });
  }

  public async logout(init?: RequestInit) {
    return this.request<undefined>('/logout', {
      method: 'POST',
      ...init,
    });
  }

  // Profile
  public async getUserProfile(init?: RequestInit) {
    return this.request<ProfileData>('/user', { method: 'GET', ...init }); 
  }

  // Transactions
  public async getUserTransactions(init?: RequestInit) {
    try {
      const response = await this.request<TransactionItem[]>('/transactions', { method: 'GET', ...init });

      // Return the actual API response
      return response;
    } catch (error) {
      console.error('Error fetching transactions:', error);
      // Return empty data on error instead of mock data
      return {
        status: 'success' as const,
        data: []
      };
    }
  }

  // Wallet
  public async getWalletStats(init?: RequestInit) {
    return this.request<{ totalTopUps: number; totalSpent: number }>('/wallet/stats', { method: 'GET', ...init });
  }

  public async initiateTopUp(body: InitiateTopUpBody, init?: RequestInit) {
    // Use Laravel protected endpoint (requires auth)
    return this.request<InitiateTopUpData>('/wallet/topup/initiate', {
      method: 'POST',
      body: JSON.stringify({ amount: body.amount }),
      ...init,
    });
  }

  public async initiateTopUpPublic(body: InitiateTopUpBody, init?: RequestInit) {
    // Temporary public endpoint for testing without auth
    return this.request<InitiateTopUpData>('/wallet/topup/initiate-public', {
      method: 'POST',
      body: JSON.stringify(body),
      ...init,
    });
  }

  public async checkPaymentStatus(body: VerifyPaymentBody, init?: RequestInit) {
    // Use Laravel verify endpoint
    return this.request<VerifyPaymentData>('/wallet/topup/verify', {
      method: 'POST',
      body: JSON.stringify(body),
      ...init,
    });
  }

  public async getTopUpHistory(init?: RequestInit) {
    return this.request<any>('/wallet/history', { method: 'GET', ...init });
  }

  // SMS Services
  public async getSmsServices(init?: RequestInit) {
    return this.request<SmsServiceItem[]>('/sms-service-api.php?action=getServices', { method: 'GET', ...init });
  }

  public async orderSmsNumber(body: OrderSmsNumberBody, init?: RequestInit) {
    return this.request<OrderSmsNumberData>('/sms-service-api.php?action=orderNumber', {
      method: 'POST',
      body: JSON.stringify(body),
      ...init,
    });
  }

  public async getSmsCode(body: GetSmsCodeBody, init?: RequestInit) {
    return this.request<GetSmsCodeData>('/sms-service-api.php?action=getSms', {
      method: 'POST',
      body: JSON.stringify(body),
      ...init,
    });
  }

  // VTU
  public async getVtuServices(init?: RequestInit) {
    return this.request<VtuServiceItem[]>('/vtu/services', { method: 'GET', ...init });
  }

  public async purchaseVtu(body: PurchaseVtuBody, init?: RequestInit) {
    return this.request<PurchaseVtuData>('/vtu/purchase', {
      method: 'POST',
      body: JSON.stringify(body),
      ...init,
    });
  }

  // Proxy
  public async getProxyServices(init?: RequestInit) {
    return this.request<ProxyServiceItem[]>('/proxy/services', { method: 'GET', ...init });
  }

  public async purchaseProxy(body: PurchaseProxyBody, init?: RequestInit) {
    return this.request<PurchaseProxyData>('/proxy/purchase', {
      method: 'POST',
      body: JSON.stringify(body),
      ...init,
    });
  }

  // Utilities
  public async testConnection(init?: RequestInit) {
    return this.request<any>('/simple-test', { method: 'GET', ...init });
  }
}

export const apiService = new ApiService(API_BASE_URL);
export default ApiService;



