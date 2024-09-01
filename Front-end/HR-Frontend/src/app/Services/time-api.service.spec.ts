import { TestBed } from '@angular/core/testing';

import { TimeApiService } from './time-api.service';

describe('TimeApiService', () => {
  let service: TimeApiService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(TimeApiService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
