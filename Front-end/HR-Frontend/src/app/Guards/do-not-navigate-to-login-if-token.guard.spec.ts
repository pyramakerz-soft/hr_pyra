import { TestBed } from '@angular/core/testing';
import { CanActivateFn } from '@angular/router';

import { doNotNavigateToLoginIfTokenGuard } from './do-not-navigate-to-login-if-token.guard';

describe('doNotNavigateToLoginIfTokenGuard', () => {
  const executeGuard: CanActivateFn = (...guardParameters) => 
      TestBed.runInInjectionContext(() => doNotNavigateToLoginIfTokenGuard(...guardParameters));

  beforeEach(() => {
    TestBed.configureTestingModule({});
  });

  it('should be created', () => {
    expect(executeGuard).toBeTruthy();
  });
});
