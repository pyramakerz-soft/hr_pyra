import { ComponentFixture, TestBed } from '@angular/core/testing';

import { HRDashboardComponent } from './hrdashboard.component';

describe('HRDashboardComponent', () => {
  let component: HRDashboardComponent;
  let fixture: ComponentFixture<HRDashboardComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [HRDashboardComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(HRDashboardComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
