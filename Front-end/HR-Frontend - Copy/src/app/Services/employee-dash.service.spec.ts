import { TestBed } from '@angular/core/testing';

import { EmployeeDashService } from './employee-dash.service';

describe('EmployeeDashService', () => {
  let service: EmployeeDashService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(EmployeeDashService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
