import { TestBed } from '@angular/core/testing';
import { CanActivateFn } from '@angular/router';

import { doNotNavigateToLoginIfTokenExistsGuard } from './do-not-navigate-to-login-if-token-exists.guard';

describe('doNotNavigateToLoginIfTokenExistsGuard', () => {
  const executeGuard: CanActivateFn = (...guardParameters) => 
      TestBed.runInInjectionContext(() => doNotNavigateToLoginIfTokenExistsGuard(...guardParameters));

  beforeEach(() => {
    TestBed.configureTestingModule({});
  });

  it('should be created', () => {
    expect(executeGuard).toBeTruthy();
  });
});
