import { TestBed } from '@angular/core/testing';

import { ClockEventService } from './clock-event.service';

describe('ClockEventService', () => {
  let service: ClockEventService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(ClockEventService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
