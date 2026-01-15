import { TestBed } from '@angular/core/testing';

import { HrStateService } from './hr-state.service';

describe('HrStateService', () => {
  let service: HrStateService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(HrStateService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
