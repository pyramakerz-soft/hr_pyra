import { ComponentFixture, TestBed } from '@angular/core/testing';

import { HrIssuesComponent } from './hr-issues.component';

describe('HrIssuesComponent', () => {
  let component: HrIssuesComponent;
  let fixture: ComponentFixture<HrIssuesComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [HrIssuesComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(HrIssuesComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
