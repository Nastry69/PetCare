import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, act } from '@testing-library/react';
import { AuthProvider, useAuth } from '../AuthContext';

/**
 * Tests unitaires du contexte d'authentification.
 * Le module axios est mocké pour éviter les appels réseau réels.
 */
vi.mock('../../api/axios', () => ({
  default: {
    get:  vi.fn(),
    post: vi.fn(),
    interceptors: {
      request:  { use: vi.fn() },
      response: { use: vi.fn() },
    },
  },
}));

import api from '../../api/axios';

// Composant de test qui expose l'état du contexte
function AuthDisplay() {
  const { user, loading } = useAuth();
  return (
    <>
      <span data-testid="user">{user ? user.email : 'null'}</span>
      <span data-testid="loading">{String(loading)}</span>
    </>
  );
}

describe('useAuth', () => {
  it('lève une erreur si utilisé hors AuthProvider', () => {
    const err = vi.spyOn(console, 'error').mockImplementation(() => {});
    expect(() => render(<AuthDisplay />)).toThrow('useAuth must be used inside AuthProvider');
    err.mockRestore();
  });
});

describe('AuthProvider — état initial', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    localStorage.clear();
  });

  it('démarre avec user=null et loading=false (pas de token)', async () => {
    vi.mocked(api.get).mockRejectedValue(new Error('no token'));

    await act(async () => {
      render(
        <AuthProvider>
          <AuthDisplay />
        </AuthProvider>
      );
    });

    expect(screen.getByTestId('user').textContent).toBe('null');
    expect(screen.getByTestId('loading').textContent).toBe('false');
  });

  it('charge l\'utilisateur depuis le token localStorage', async () => {
    localStorage.setItem('token', 'fake-jwt-token');
    vi.mocked(api.get).mockResolvedValue({
      data: { email: 'saved@petcare.fr', nom: 'Test', prenom: 'User' },
    });

    await act(async () => {
      render(
        <AuthProvider>
          <AuthDisplay />
        </AuthProvider>
      );
    });

    expect(screen.getByTestId('user').textContent).toBe('saved@petcare.fr');
  });

  it('supprime le token si le chargement initial échoue (token expiré)', async () => {
    localStorage.setItem('token', 'token-invalide');
    vi.mocked(api.get).mockRejectedValue(new Error('401 Unauthorized'));

    await act(async () => {
      render(
        <AuthProvider>
          <AuthDisplay />
        </AuthProvider>
      );
    });

    expect(localStorage.getItem('token')).toBeNull();
    expect(screen.getByTestId('user').textContent).toBe('null');
  });
});

describe('AuthProvider — login', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    localStorage.clear();
  });

  it('stocke le token et met à jour user après login réussi', async () => {
    vi.mocked(api.post).mockResolvedValue({ data: { token: 'jwt-token-123' } });
    vi.mocked(api.get).mockResolvedValueOnce({ data: null }) // loadUser initial
                       .mockResolvedValueOnce({ data: { email: 'jean@petcare.fr' } }); // /me après login

    let loginFn: (e: string, p: string) => Promise<void>;

    function Wrapper() {
      const { login } = useAuth();
      loginFn = login;
      return <AuthDisplay />;
    }

    await act(async () => {
      render(
        <AuthProvider>
          <Wrapper />
        </AuthProvider>
      );
    });

    await act(async () => {
      await loginFn('jean@petcare.fr', 'motdepasse123');
    });

    expect(localStorage.getItem('token')).toBe('jwt-token-123');
    expect(screen.getByTestId('user').textContent).toBe('jean@petcare.fr');
  });
});

describe('AuthProvider — logout', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    localStorage.clear();
  });

  it('supprime le token et remet user à null', async () => {
    localStorage.setItem('token', 'mon-token');
    vi.mocked(api.get).mockResolvedValue({ data: { email: 'jean@petcare.fr' } });

    let logoutFn: () => void;

    function Wrapper() {
      const { logout } = useAuth();
      logoutFn = logout;
      return <AuthDisplay />;
    }

    await act(async () => {
      render(
        <AuthProvider>
          <Wrapper />
        </AuthProvider>
      );
    });

    act(() => {
      logoutFn();
    });

    expect(localStorage.getItem('token')).toBeNull();
    expect(screen.getByTestId('user').textContent).toBe('null');
  });
});

describe('AuthProvider — register', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    localStorage.clear();
  });

  it("stocke le token et crée l'utilisateur après inscription réussie", async () => {
    vi.mocked(api.get).mockRejectedValue(new Error('no token')); // initial loadUser
    vi.mocked(api.post).mockResolvedValue({
      data: {
        token: 'register-token',
        user: { email: 'nouveau@petcare.fr', nom: 'Dupont', prenom: 'Jean' },
      },
    });

    let registerFn: (...args: unknown[]) => Promise<void>;

    function Wrapper() {
      const { register } = useAuth();
      registerFn = register;
      return <AuthDisplay />;
    }

    await act(async () => {
      render(
        <AuthProvider>
          <Wrapper />
        </AuthProvider>
      );
    });

    await act(async () => {
      await registerFn('Dupont', 'Jean', 'nouveau@petcare.fr', 'motdepasse123');
    });

    expect(localStorage.getItem('token')).toBe('register-token');
    expect(screen.getByTestId('user').textContent).toBe('nouveau@petcare.fr');
  });
});
