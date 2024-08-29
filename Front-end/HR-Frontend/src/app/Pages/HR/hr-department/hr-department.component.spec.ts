import { ComponentFixture, TestBed } from '@angular/core/testing';

import { HrDepartmentComponent } from './hr-department.component';

describe('HrDepartmentComponent', () => {
  let component: HrDepartmentComponent;
  let fixture: ComponentFixture<HrDepartmentComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [HrDepartmentComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(HrDepartmentComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
