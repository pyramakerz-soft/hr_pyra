import { TestBed } from '@angular/core/testing';
import { CanActivateFn } from '@angular/router';

import { navigateIfHrGuard } from './navigate-if-hr.guard';

describe('navigateIfHrGuard', () => {
  const executeGuard: CanActivateFn = (...guardParameters) => 
      TestBed.runInInjectionContext(() => navigateIfHrGuard(...guardParameters));

  beforeEach(() => {
    TestBed.configureTestingModule({});
  });

  it('should be created', () => {
    expect(executeGuard).toBeTruthy();
  });
});
