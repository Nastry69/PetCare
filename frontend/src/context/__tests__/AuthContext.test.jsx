import React from 'react';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, act } from '@testing-library/react';
import api from '../../api/axios';
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

  it('démarre avec user=null et loading=false (pas de token stocké)', async () => {
    // Pas de token → api.get ne sera pas appelé → on mock quand même par sécurité
    api.get.mockRejectedValue(new Error('no token'));

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

  it("charge l'utilisateur depuis le token localStorage", async () => {
    localStorage.setItem('token', 'fake-jwt-token');
    api.get.mockResolvedValue({
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
    api.get.mockRejectedValue(new Error('401 Unauthorized'));

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
    // loadUser initial (pas de token)
    api.get.mockRejectedValue(new Error('no token'));

    let loginFn;

    function Wrapper() {
      const auth = useAuth();
      loginFn = auth.login;
      return <AuthDisplay />;
    }

    await act(async () => {
      render(
        <AuthProvider>
          <Wrapper />
        </AuthProvider>
      );
    });

    // Prépare les mocks pour le login
    api.post.mockResolvedValue({ data: { token: 'jwt-token-123' } });
    api.get.mockResolvedValue({ data: { email: 'jean@petcare.fr' } });

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
    api.get.mockResolvedValue({ data: { email: 'jean@petcare.fr' } });

    let logoutFn;

    function Wrapper() {
      const auth = useAuth();
      logoutFn = auth.logout;
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
    api.get.mockRejectedValue(new Error('no token'));

    let registerFn;

    function Wrapper() {
      const auth = useAuth();
      registerFn = auth.register;
      return <AuthDisplay />;
    }

    await act(async () => {
      render(
        <AuthProvider>
          <Wrapper />
        </AuthProvider>
      );
    });

    api.post.mockResolvedValue({
      data: {
        token: 'register-token',
        user: { email: 'nouveau@petcare.fr', nom: 'Dupont', prenom: 'Jean' },
      },
    });

    await act(async () => {
      await registerFn('Dupont', 'Jean', 'nouveau@petcare.fr', 'motdepasse123');
    });

    expect(localStorage.getItem('token')).toBe('register-token');
    expect(screen.getByTestId('user').textContent).toBe('nouveau@petcare.fr');
  });
});
