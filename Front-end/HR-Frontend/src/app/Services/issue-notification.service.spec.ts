import { TestBed } from '@angular/core/testing';

import { IssueNotificationService } from './issue-notification.service';

describe('IssueNotificationService', () => {
  let service: IssueNotificationService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(IssueNotificationService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
