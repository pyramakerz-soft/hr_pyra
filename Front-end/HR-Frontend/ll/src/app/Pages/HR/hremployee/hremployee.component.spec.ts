import { ComponentFixture, TestBed } from '@angular/core/testing';

import { HREmployeeComponent } from './hremployee.component';

describe('HREmployeeComponent', () => {
  let component: HREmployeeComponent;
  let fixture: ComponentFixture<HREmployeeComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [HREmployeeComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(HREmployeeComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
