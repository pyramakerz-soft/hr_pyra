import { ComponentFixture, TestBed } from '@angular/core/testing';

import { HrBoundersComponent } from './hr-bounders.component';

describe('HrBoundersComponent', () => {
  let component: HrBoundersComponent;
  let fixture: ComponentFixture<HrBoundersComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [HrBoundersComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(HrBoundersComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
